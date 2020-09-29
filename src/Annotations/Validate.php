<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Container\Container;
use Minimal\Annotation\AnnotationInterface;

/**
 * 验证器
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Validate implements AnnotationInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected string $class)
    {}

    /**
     * 获取在上下文中的Key
     */
    public function getContextKey() : ?string
    {
        return 'validate';
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
        return 0;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        return $this->container->make($this->class);
    }
}