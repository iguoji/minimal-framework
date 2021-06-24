<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Http;

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
        $path = $request->path();
        if (in_array($path, ['/favicon.ico'])) {
            return false;
        }
        // 非网页请求
        $accept = $request->header('Accept');
        if (str_ends_with($path, '.html') && false === strpos($accept, 'text/html')) {
            return false;
        }

        // 返回结果
        return true;
    }
}