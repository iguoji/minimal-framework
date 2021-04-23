<?php
declare(strict_types=1);

namespace Minimal\Listeners\Http;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 在收到一个完整的 HTTP 请求后，会先行回调此函数。
 */
class OnHttpBefore implements Listener
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
            'Server:OnHttpBefore',
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

        // Favicon
        if (($request->server['request_uri'] ?? $request->server['path_info']) == '/favicon.ico') {
            $response->end();
            return false;
        }

        // 返回结果
        return true;
    }
}