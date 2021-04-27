<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Closure;
use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 事件类
 */
class Event
{
    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 批量绑定
     */
    public function bind(string $name, Closure|array|string $class = null) : void
    {
        if ($class instanceof Closure) {
            $this->app->event->on($name, $class);
        } else {
            $class = $name;
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
        if (!isset($this->bindings[$name])) {
            $this->bindings[$name] = [];
        }

        $index = count($this->bindings[$name]);
        foreach ($this->bindings[$name] as $key => $array) {
            if ($priority > $array['priority']) {
                $index = $key;
                break;
            }
        }

        array_splice($this->bindings[$name], $index, 0, [[
            'callable'  =>  $callback,
            'priority'  =>  $priority,
        ]]);
    }

    /**
     * 触发事件
     */
    public function trigger(string $name, array $arguments = []) : bool
    {
        try {
            $this->app->log->debug($name);
            $events = $this->bindings[$name] ?? [];
            foreach ($events as $key => $array) {
                $bool = $this->app->call($array['callable'], $name, $arguments);
                if (false === $bool) {
                    return false;
                }
            }
        } catch (Throwable $th) {
            $this->app->log->error($th->getMessage(), [
                'File'   =>  $th->getFile(),
                'Line'   =>  $th->getLine(),
            ]);
            return false;
        }
        return true;
    }
}