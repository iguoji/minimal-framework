<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Contracts\Annotation;

/**
 * Http请求域名限制
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Domain implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected array $domains = ['*'])
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
        return 100;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        return $this->domains;
    }
}