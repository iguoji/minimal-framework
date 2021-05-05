<?php
declare(strict_types=1);

namespace Minimal\Listeners\Cache;

use Throwable;
use Swoole\Coroutine;
use Minimal\Application;
use Minimal\Contracts\Listener;
use Minimal\Cache\Manager as Cache;

/**
 * 缓存初始化事件
 */
class OnInit implements Listener
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 事件列表
     */
    public function events() : array
    {
        return [
            'Cache:OnInit',
            'Server:OnWorkerStart',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 启动缓存
        if ($event == 'Server:OnWorkerStart') {
            // Swoole\Server
            $server = $arguments[0];
            // int Worker 进程 id（非进程的 PID）
            $workerId = $arguments[1];
            // 协程环境
            Coroutine::create(function() use($server){
                // 实例化缓存
                $this->app->set('cache', new Cache($this->app->config->get('cache', []), $server->setting['worker_num'] + $server->setting['task_worker_num']));
            });
        }

        // 返回结果
        return true;
    }
}