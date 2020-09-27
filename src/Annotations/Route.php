<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Application;
use Minimal\Contracts\Annotation;

/**
 * 添加路由
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app, protected string $path, protected array $methods = ['POST'])
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
        return 0;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        return $this->app->addRoute(
            $this->path,
            array_map(fn($s) => strtoupper($s), $this->methods),
            $context,
        );
    }
}