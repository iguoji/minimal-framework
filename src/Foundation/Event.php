<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Closure;
use RuntimeException;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 事件类
 */
class Event
{
    /**
     * 事件集合
     */
    protected array $events = [];

    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 批量绑定
     */
    public function bind(string $name, Closure|array|string $class) : void
    {
        if ($class instanceof Closure) {
            $this->app->event->on($name, $class);
        } else {
            $listener = $this->app->get($class);
            if (!$listener instanceof Listener) {
                throw new Exception(sprintf('listener "%s" must implements "%s"', $class, Listener::class));
            }

            foreach ($listener->events() as $key => $value) {
                if (is_string($key)) {
                    $this->on($key, [$listener, 'handle'], $value);
                } else {
                    $this->on($value, [$listener, 'handle'], 0);
                }
            }
        }
    }

    /**
     * 监听事件
     */
    public function on(string $name, Closure|array $callback, int $priority = 0) : void
    {
        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }
        $index = count($this->events[$name]);
        foreach ($this->events[$name] as $key => $array) {
            if ($priority > $array['priority']) {
                $index = $key;
                break;
            }
        }
        array_splice($this->events[$name], $index, 0, [[
            'callable'  =>  $callback,
            'priority'  =>  $priority,
        ]]);
    }

    /**
     * 触发事件
     */
    public function trigger(string $name, array $arguments = []) : bool
    {
        $events = $this->events[$name] ?? [];
        foreach ($events as $key => $array) {
            $bool = $this->app->call($array['callable'], $name, $arguments);
            if (false === $bool) {
                return false;
            }
        }
        return true;
    }
}