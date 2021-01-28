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
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        // 路径规则
        if (isset($context['routepath'])) {
            $rule = $context['routepath'][0];
        } else if (!isset($this->path)) {
            $className = basename(str_replace('\\', '/', $context['class']));
            $className = lcfirst($className);
            $methodName = lcfirst($context['method']);
            $rule = $className . '/' . $methodName;
        } else {
            $rule = $this->path;
        }
        if (isset($context['routeprefix'])) {
            $rule = $context['routeprefix'][0] . '/' . ltrim($rule, '/');
        }
        $rule = '/' . ltrim($rule, '/');
        // 请求方式
        $methods = array_map(fn($s) => strtoupper($s), $context['methods'][0] ?? $this->methods);

        // 回调对象
        if (isset($context['instance']) && isset($context['method'])) {
            $context['callable'] = [$context['instance'], $context['method']];
        }
        // 域名列表
        $context['domains'] = isset($context['domain']) ? $context['domain'][0] : ['*'];
        // 中间件列表
        $context['middlewares'] = empty($context['middleware']) ? [] : $context['middleware'][0];

        // 添加路由
        return $this->app->addRoute(
            $rule,
            $methods,
            $context,
        );
    }
}