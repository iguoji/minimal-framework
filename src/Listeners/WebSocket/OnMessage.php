<?php
declare(strict_types=1);

namespace Minimal\Listeners\WebSocket;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 当服务器收到来自客户端的数据帧时会回调此函数。
 */
class OnMessage implements Listener
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
            'Server:OnMessage',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\WebSocket\Server
        $server = $arguments[0];
        // Swoole\WebSocket\Frame
        $frame = $arguments[1];

        // 返回结果
        return true;
    }
}