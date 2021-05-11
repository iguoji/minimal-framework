<?php
declare(strict_types=1);

namespace Minimal\Listeners\Http;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 在收到一个完整的 HTTP 请求后，并处理完了之后，会回调此函数。
 */
class OnHttpAfter implements Listener
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
            'Server:OnHttpAfter',
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

        // 有效的响应
        if ($response->isWritable()) {
            // Session
            $sessionConfig = $this->app->session->getConfig();
            $response->cookie(
                $sessionConfig['name'],
                $this->app->session->getSessionId(),
                time() + $sessionConfig['expire'],
                $sessionConfig['path'],
                $sessionConfig['domain'],
                $sessionConfig['secure'],
                $sessionConfig['httponly'],
                $sessionConfig['samesite'],
                $sessionConfig['priority'],
            );

            // 结束响应
            $response->end($response->getContent());
        }

        // 释放资源
        $this->app->context->flush();

        // 返回结果
        return true;
    }
}