<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Container;
use Minimal\Contracts\Annotation;

/**
 * 依赖注册到容器
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Inject implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected string $interface = '', protected string $alias = '')
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
        return [Attribute::TARGET_CLASS];
    }

    /**
     * 获取优先级
     */
    public function getPriority() : int
    {
        return 100;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        $this->interface = $this->interface ?: $context['class'];
        $this->alias = $this->alias ?: strtolower(basename(str_replace('\\', '/', $context['class'])));
        $ins = $context['instance'] ?? $this->container->make($context['class']);
        $this->container->set($this->interface, $ins);
        if (!empty($this->alias)) {
            $this->container->setAlias($this->alias, $this->interface);
        }
        return null;
    }
}