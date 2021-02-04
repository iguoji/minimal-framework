<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Application;
use Minimal\Annotation\AnnotationInterface;

/**
 * 添加路由
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route implements AnnotationInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app, protected ?string $path = null, protected array $methods = ['POST'])
    {}

    /**
     * 获取在上下文中的Key
     */
    public function getContextKey() : ?string
    {
        return null;
    }

    /**
     * 获取目标
     */
    public function getTargets() : array
    {
        return [Attribute::TARGET_METHOD];
    }

    /**
     * 获取优先级
     */
    public function getPriority() : int
    {
        return -1;
    }

    /**
     * 解析参数
     */
    public function arguments(string|array $data) : array
    {
        $array = empty($data) ? [] : $data;
        $array = is_string($array) ? [$array] : $array;
        $reuslt = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $reuslt = array_merge($reuslt, $item);
            } else {
                $reuslt[] = $item;
            }
        }
        return $reuslt;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        // 类和方法
        $className = basename(str_replace('\\', '/', $context['class']));
        $className = lcfirst($className);
        $methodName = lcfirst($context['method']);

        // 路径规则
        if (isset($context['routePath'])) {
            // 指定了完整路径
            $rule = $context['routePath'][0];
        } else if (isset($context['routePrefix'])) {
            // 指定了前缀，只需补充方法名
            $rule = $context['routePrefix'][0] . '/' . ltrim($methodName, '/');
        } else {
            // 其他情况下用类名 + 方法名
            $rule = $this->path ?? $className . '/' . $methodName;
        }
        $rule = '/' . ltrim($rule, '/');

        // 请求方式
        $methods = $this->arguments($context['routeMethod'] ?? []) ?: $this->methods;

        // 回调对象
        if (isset($context['instance']) && isset($context['method'])) {
            $context['callable'] = [$context['instance'], $context['method']];
        }

        // 域名列表
        $context['domains'] = isset($context['domain']) ? $context['domain'][0] : ['*'];

        // 中间件列表
        $context['middlewares'] = $this->arguments($context['middleware'] ?? []);

        // 添加路由
        return $this->app->addRoute(
            $rule,
            $methods,
            $context,
        );
    }
}