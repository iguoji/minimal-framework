<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use Minimal\Application;
use Minimal\Contracts\Annotation;

#[Attribute(Attribute::TARGET_CLASS, Attribute::TARGET_METHOD)]
class Middleware implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app, protected array $middlewares = [])
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
        $event = $context['class'] . ':' . $context['method'] . ':OnBefore';
        return array_walk($this->middlewares, fn($middle) => $this->app->on($event, $middle));
    }
}