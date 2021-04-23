<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * Worker 进程 Reload 之前触发此事件，在 Manager 进程中回调
 */
class OnBeforeReload implements Listener
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
            'Server:OnBeforeReload',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];

        // 返回结果
        return true;
    }
}