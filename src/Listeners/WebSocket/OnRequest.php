<?php
declare(strict_types=1);

namespace Minimal\Listeners\WebSocket;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 在收到一个完整的 HTTP 请求后，会回调此函数。
 */
class OnRequest implements Listener
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
            'Server:OnRequest',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Http\Request
        $request = $this->app->request->setHandle($arguments[0]);
        // Swoole\Http\Response
        $response = $this->app->response->setHandle($arguments[1]);

        // 前置事件
        $bool = $this->app->event->trigger('Server:OnHttpBefore', [$request, $response]);
        if (false === $bool) {
            return false;
        }

        // 请求处理
        $bool = $this->app->event->trigger('Server:OnHttp', [$request, $response]);
        if (false === $bool) {
            return false;
        }

        // 后置事件
        return $this->app->event->trigger('Server:OnHttpAfter', [$request, $response]);
    }
}