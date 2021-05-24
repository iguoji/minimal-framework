<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Minimal\Support\Traits\Alias;

/**
 * 容器类
 */
class Container
{
    /**
     * 引入别名
     */
    use Alias;

    /**
     * 对象实例
     */
    protected array $instances = [];

    /**
     * 获取实例
     */
    public function get(string $id, ...$parameters) : mixed
    {
        $id = $this->getAlias($id);
        if (!$this->has($id)) {
            $this->instances[$id] = $this->make($id, ...$parameters);
        }
        return $this->instances[$id];
    }

    /**
     * 设置实例
     */
    public function set(string $id, mixed $instance) : void
    {
        if (is_object($instance)) {
            $this->setAlias($id, $instance::class);
        }
        $id = $this->getAlias($id);
        $this->instances[$id] = $instance;
    }

    /**
     * 实例是否存在
     */
    public function has(string $id) : bool
    {
        $id = $this->getAlias($id);
        return isset($this->instances[$id]);
    }

    /**
     * 创建实例
     */
    public function make(string $id, ...$parameters) : mixed
    {
        // 获取别名
        $id = $this->getAlias($id);
        // 解析对象
        $instance = $this->newInstance($id, $parameters ?? []);
        // 保存对象
        return $instance;
    }

    /**
     * 调用函数
     */
    public function call(ReflectionFunctionAbstract|Closure|array $callable, ...$parameters)
    {
        // 方法/函数反射对象
        $reflection = $callable instanceof Closure ? new ReflectionFunction($callable) : new ReflectionMethod(...$callable);

        // 解析参数
        $invokeParams = $this->getParameters($reflection);
        // 合并参数
        $parameters = array_merge($invokeParams, $parameters);
        // 根据情况判断是否需要调用者
        if ($reflection instanceof ReflectionMethod) {
            if (is_array($callable) && is_object($callable[0])) {
                // 提供了调用者
                array_unshift($parameters, $callable[0]);
            } else {
                // 需要调用者
                array_unshift($parameters, $this->newInstance($callable[0]));
            }
        }

        // 返回结果
        return $reflection->invoke(...$parameters);
    }

    /**
     * 获取实例
     */
    private function newInstance(ReflectionClass|string $class, array $parameters = []) : mixed
    {
        // 反射对象
        $class = is_string($class) ? new ReflectionClass($class) : $class;
        // 最终实例
        $instance = null;
        // 无法构造
        if (!$class->isInstantiable()) {
            throw new Exception("{$class->getName()} cannot instantiate");
        }
        // 构造方法
        $method = $class->getConstructor();
        // 构造参数
        if (!is_null($method)) {
            // 方法参数
            $methodParameter = $this->getParameters($method);
            // 合并参数
            $parameters = array_merge($methodParameter, $parameters);
        }
        // 获得实例
        $instance = $class->newInstanceArgs($parameters);
        // 返回实例
        return $instance;
    }

    /**
     * 获取参数
     */
    private function getParameters(ReflectionFunctionAbstract $method) : array
    {
        // 最终结果
        $result = [];
        // 循环参数
        $params = $method->getParameters();
        foreach ($params as $key => $param) {
            // 参数类型
            $paramType = $param->getType();
            if (!is_null($paramType) && !$paramType->isBuiltin()) {
                // 类型名称
                $className = $paramType->getName();
                // 判断是否已经存在
                if ($this->has($className)) {
                    // 已经存在、直接赋值
                    $result[] = $this->get($className);
                } else {
                    // 不存在、创建新的
                    $result[] = $this->newInstance($className);
                }
                continue;
            }
            // 可空或可选
            if (empty($class) || $class->allowsNull() || $class->isOptional()) {
                break;
            }
        }
        // 返回结果
        return $result;
    }

    /**
     * 快捷属性
     */
    public function __get(string $id) : mixed
    {
        return $this->get($id);
    }
}