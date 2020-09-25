<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use UnexpectedValueException;
use Minimal\Container;
use Minimal\Application;
use Minimal\Contracts\Annotation;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 绑定事件
 */
#[Attribute]
class Listener implements Annotation
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected Application $app)
    {}

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
        return 0;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        $listener = $this->container->make($context['class']);
        if (!$listener instanceof ListenerInterface) {
            throw new UnexpectedValueException(sprintf('listener "%s" must implements "%s"', $context['class'], ListenerInterface::class));
        }
        foreach ($listener->events() as $key => $value) {
            if (is_string($key)) {
                $this->app->on($key, [$listener, 'handle'], $value);
            } else {
                $this->app->on($value, [$listener, 'handle'], 0);
            }
        }
        return null;
    }
}