<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 接收到数据时回调此函数，发生在 worker 进程中。
 */
class OnReceive implements Listener
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
            'Server:OnReceive',
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
        // string 收到的数据内容，可能是文本或者二进制内容
        $data = $arguments[3];

        // 返回结果
        return true;
    }
}