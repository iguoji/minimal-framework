<?php
declare(strict_types=1);

namespace Minimal\Listeners\Database;

use Throwable;
use Swoole\Coroutine;
use Minimal\Application;
use Minimal\Contracts\Listener;
use Minimal\Database\Manager as Database;

/**
 * 数据库初始化事件
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

        // 协程环境
        // Coroutine::create(function() use($server){
            // 数据库配置
            $config = $this->app->config->get('db', []);
            // 进程数量
            $workerNum = $server->setting['worker_num'] + $server->setting['task_worker_num'];
            // 实例化数据库
            $db = new Database($config, $workerNum);

            // 保存数据库
            $this->app->set('database', $db);
            // 触发事件
            $this->app->event->trigger('Database:OnInit', [$db, $config, $workerNum]);
        // });

        // 返回结果
        return true;
    }
}