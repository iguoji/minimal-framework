<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Contracts\Annotation;

/**
 * 设置中间件
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected array $middlewares = [])
    {}

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
        return $this->middlewares;
    }
}