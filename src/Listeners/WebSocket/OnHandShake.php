<?php
declare(strict_types=1);

namespace Minimal\Listeners\WebSocket;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * WebSocket 建立连接后进行握手。
 * WebSocket 服务器会自动进行 handshake 握手的过程，如果用户希望自己进行握手处理，可以设置 onHandShake 事件回调函数。
 */
class OnHandShake implements Listener
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
            'Server:OnHandShake',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Http\Request
        $request = $arguments[0];
        // Swoole\Http\Response
        $response = $arguments[1];

        // 返回结果
        return true;
    }
}