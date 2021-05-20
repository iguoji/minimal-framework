<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * TCP 客户端连接关闭后，在 Worker 进程中回调此函数。
 */
class OnClose implements Listener
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
            'Server:OnClose',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];
        // int 连接的文件描述符
        $fd = $arguments[1];
        // int 连接所在的 Reactor 线程 ID
        $reactorId = $arguments[2];

        // 返回结果
        return true;
    }
}