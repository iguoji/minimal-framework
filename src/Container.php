<?php
declare(strict_types=1);

namespace Minimal;

use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;

/**
 * 容器类
 */
class Container
{
    /**
     * 对象实例
     */
    protected array $instances = [];

    /**
     * 别名集合
     */
    protected array $aliases = [];

    /**
     * 设置别名
     */
    public function setAlias(string $key, string $value) : void
    {
        if ($key != $value) {
            $this->aliases[$key] = $value;
        }
    }

    /**
     * 是否存在指定别名
     */
    public function hasAlias(string $key) : bool
    {
        return isset($this->aliases[$key]);
    }

    /**
     * 获取所有别名
     */
    public function getAliases() : array
    {
        return $this->aliases;
    }

    /**
     * 获取别名
     */
    public function getAlias(string $key) : string
    {
        return $this->hasAlias($key)
            ? $this->getAlias($this->aliases[$key])
            : $key;
    }

    /**
     * 获取实例
     */
    public function get(string $id) : mixed
    {
        $id = $this->getAlias($id);
        if (!$this->has($id)) {
            return $this->make($id);
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
     * @param $callable className::methodName
     * @param $callable [className, methodName]
     * @param $callable [classInstance, methodName]
     */
    public function call($callable, ...$parameters)
    {
        // 类的方法
        $method = new ReflectionMethod(...$callable);
        // 上下文$this
        $context = $this;
        // 静态方法
        if ($method->isStatic()) {
            // 不需调用者
            $context = null;
        } else if (is_array($callable) && is_object($callable[0])) {
            // 提供了调用者
            $context = $callable[0];
        } else {
            // 需要调用者
            $context = $this->newInstance($callable[0]);
        }
        // 解析参数
        $invokeParams = $this->getParameters($method);
        // 合并参数
        $parameters = array_merge($invokeParams, $parameters);
        // 返回结果
        return $method->invoke($context, ...$parameters);
    }

    /**
     * 获取实例
     */
    private function newInstance($class, array $parameters = []) : mixed
    {
        // 反射对象
        $class = is_string($class) ? new ReflectionClass($class) : $class;
        // 最终实例
        $instance = null;
        // 无法构造
        if (!$class->isInstantiable()) {
            // 寻找子类
            foreach ($this->instances as $key => $obj) {
                if (is_subclass_of($obj, $class->getName())) {
                    $instance = $obj;
                    break;
                }
            }
            // 没有找到
            if (is_null($instance)) {
                throw new Exception("{$class->getName()} cannot instantiate");
            }
        } else {
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
        }
        // 返回实例
        return $instance;
    }

    /**
     * 获取参数
     */
    private function getParameters($method)
    {
        // 最终结果
        $result = [];
        // 得到参数
        $params = $method->getParameters();
        // 循环参数
        foreach ($params as $key => $param) {
            // 参数类型
            $paramType = $param->getType();
            if (! $paramType->isBuiltin()) {
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
    public function __get($id)
    {
        return $this->get($id);
    }
}