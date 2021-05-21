<?php
declare(strict_types=1);

namespace Minimal\Server\Listener;

use Throwable;
use Minimal\Application;
use Minimal\Cache\Manager;
use Minimal\Contracts\Listener;

/**
 * 缓存事件
 */
class Cache implements Listener
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
            'Server:OnWorkerStart',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];
        // int Worker 进程 id（非进程的 PID）
        $workerId = $arguments[1];

        if ($event == 'Server:OnWorkerStart') {
            // 缓存配置
            $config = $this->app->config->get('cache', []);
            // 进程数量
            $workerNum = $server->setting['worker_num'] + $server->setting['task_worker_num'];
            // 实例化缓存
            $cache = new Manager($config, $workerNum);

            // 保存缓存
            $this->app->set('cache', $cache);
            // 触发事件
            $this->app->event->trigger('Cache:OnInit', [$cache, $config, $workerNum]);
        }

        // 返回结果
        return true;
    }
}