<?php
declare(strict_types=1);

namespace Minimal\Listeners\Http;

use Throwable;
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

        // 协程处理
        return \Swoole\Coroutine::create(function() use($request, $response){
            // 最终结果
            $result = [
                'code'      =>  200,
                'message'   =>  '恭喜您、操作成功！',
                'data'      =>  [],
            ];
            try {
                // 匹配路由
                $route = $this->app->route->dispatch(
                    $request->header['host']
                    , $request->server['request_method']
                    , $request->server['request_uri'] ?? $request->server['path_info']
                );
                if (empty($route)) {
                    throw new Exception('Sorry. api not found');
                }

                // 更新容器中的请求和响应对象
                $this->app->set('request', $request);
                $this->app->set('response', $response);

                // 回调和中间件
                [$callable, $middlewares] = $route;

                // 中间件 + 用户操作
                $callback = array_reduce(array_reverse($middlewares ?? []), function($next, $middleware) use($request, $response){
                    return function() use($middleware, $request, $next) {
                        return call_user_func_array($middleware, [$request, $next]);
                    };
                }, fn() => $this->app->call($callable, $request, $response));

                // 保存控制器返回的结果
                $result['data'] = $callback();
                if (is_object($result['data'])) {
                    $result = (string) $result['data'];
                }
            } catch (Throwable $th) {
                // 保存异常引起的结果
                $result = array_merge($result, [
                    'code'      =>  $th->getCode() ?: 500,
                    'message'   =>  $th->getMessage(),
                    'file'      =>  $th->getFile(),
                    'line'      =>  $th->getLine(),
                    'data'      =>  method_exists($th, 'getData') ? $th->getData() : [],
                    'trace'     =>  $th->getTrace(),
                ]);
            }

            // 输出结果
            if ($response->isWritable()) {
                $response->status(200);
                if (is_string($result)) {
                    $response->header('Content-Type', 'text/html;charset=utf-8');
                    $response->end($result);
                } else {
                    $response->header('Content-Type', 'application/json;charset=utf-8');
                    $response->end(json_encode($result));
                }
            }
        }) > 0;
    }
}