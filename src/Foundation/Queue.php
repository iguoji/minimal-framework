<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;

/**
 * 队列类
 */
class Queue
{
    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 投递任务
     */
    public function task(array $data, callable $callback = null) : void
    {
        if (is_callable($callback)) {
            $this->app->server->task($data, -1, $callback);
        } else {
            $this->app->server->task($data, -1);
        }
    }
}