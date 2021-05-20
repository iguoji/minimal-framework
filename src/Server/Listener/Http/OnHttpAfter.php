<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Http;

use Throwable;
use Swoole\Coroutine;
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

        // 结束响应
        $response->end();

        // 清理上下文
        $this->app->context->clear();

        // 返回结果
        return true;
    }
}