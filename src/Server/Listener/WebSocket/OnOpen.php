<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\WebSocket;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 当 WebSocket 客户端与服务器建立连接并完成握手后会回调此函数。
 */
class OnOpen implements Listener
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
            'Server:OnOpen',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\WebSocket\Server
        $server = $arguments[0];
        // Swoole\Http\Request
        $request = $arguments[1];

        // 返回结果
        return true;
    }
}