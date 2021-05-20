<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 接收到 UDP 数据包时回调此函数，发生在 worker 进程中。
 */
class OnPacket implements Listener
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
            'Server:OnPacket',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];
        // string 收到的数据内容，可能是文本或者二进制内容
        $data = $arguments[1];
        // array 客户端信息包括 address/port/server_socket 等多项客户端信息数据
        $clientInfo = $arguments[2];

        // 返回结果
        return true;
    }
}