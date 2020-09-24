<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Application;
use Minimal\Contracts\Annotation;

#[Attribute(Attribute::TARGET_METHOD)]
class Route implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app, protected string $path, protected array $methods)
    {}

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
        return $this->app->addRoute($this->path, $this->methods, [$context['instance'], $context['method']], $context[Domain::class] ?? ['*']);
    }
}