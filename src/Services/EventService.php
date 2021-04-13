<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Foundation\Event;
use Minimal\Contracts\Service;

/**
 * 事件服务类
 */
class EventService implements Service
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 注册服务
     */
    public function register() : void
    {
        $event = $this->app->make(Event::class);
        $this->app->set('event', $event);
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {
        $listeners = $this->scanClass(dirname(__DIR__) . '/Listeners/', 'Minimal\\Listeners\\');

        if ($this->app->has('config')) {
            $userListeners = $this->app->config->get('listeners', []);
            $listeners = array_merge($listeners, $userListeners);
        }

        foreach ($listeners as $key => $listener) {
            $this->app->event->bind('', $listener);
        }
    }

    /**
     * 获取指定目录下的PHP类
     */
    public function scanClass(string $folder, string $namespace) : array
    {
        $classes = [];
        $paths = glob($folder . '*', GLOB_MARK);
        foreach ($paths as $path) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
            if (is_dir($path)) {
                $classes = array_merge($classes, $this->scanClass($path, $namespace . $filename . '\\'));
            } else {
                $classes[] = $namespace . $filename;
            }
        }
        return $classes;
    }
}