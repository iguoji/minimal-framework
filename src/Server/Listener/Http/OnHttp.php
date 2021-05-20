<?php
declare(strict_types=1);

namespace Minimal\Server\Listener\Http;

use Throwable;
use Swoole\Coroutine;
use Minimal\Application;
use Minimal\Contracts\Listener;
use Minimal\Foundation\Exception;

/**
 * 在收到一个完整的 HTTP 请求后，会回调此函数。
 */
class OnHttp implements Listener
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
            'Server:OnHttp',
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

        try {
            // 匹配路由
            $route = $this->app->route->dispatch($request->host(), $request->path());
            if (empty($route)) {
                throw new Exception('Sorry. api not found');
            }
            // 执行功能
            $result = $this->app->call($route, $request, $response);
            if (! $result instanceof \Minimal\Http\Response) {
                $response->json($result);
            }
        } catch (Throwable $th) {
            // 出现异常
            $response->exception($th);
        }

        // 返回结果
        return true;
    }
}