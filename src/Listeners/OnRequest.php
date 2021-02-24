<?php
declare(strict_types=1);

namespace Minimal\Events;

use Throwable;
use RuntimeException;
use Swoole\Coroutine;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 请求事件
 */
#[Listener]
class OnRequest implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnRequestBefore', 'Application:OnRequest', 'Application:OnRequestAfter'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 前置事件
        if ($event == 'Application:OnRequestBefore') {
            return $this->onHttpBefore($arguments[0], $arguments[1]);
        }

        // 请求事件
        if ($event == 'Application:OnRequest') {
            return $this->onHttp($arguments[0], $arguments[1]);
        }

        // 后置事件
        if ($event == 'Application:OnRequestAfter') {
            return $this->onHttpAfter($arguments[0], $arguments[1]);
        }
        // 返回结果
        return true;
    }

    /**
     * Http - 前置事件
     */
    public function onHttpBefore($req, $res) : bool
    {
        // Favicon
        if (($req->server['request_uri'] ?? $req->server['path_info']) == '/favicon.ico') {
            $res->end();
            return false;
        }

        // 返回结果
        return true;
    }

    /**
     * Http - 请求处理
     */
    public function onHttp($req, $res) : bool
    {
        // 协程处理
        return Coroutine::create(function() use($req, $res){
            // 最终结果
            $result = [
                'code'      =>  200,
                'message'   =>  '恭喜您、操作成功！',
                'data'      =>  [],
            ];
            try {
                // 匹配路由
                $route = $this->app->getRoute(
                    $req->server['request_uri'] ?? $req->server['path_info'],
                    $req->header['host']
                );
                if (is_null($route)) {
                    throw new RuntimeException('api not found', 404);
                }
                if (!in_array(strtoupper($req->server['request_method']), $route['methods'])) {
                    throw new RuntimeException('method not allowed');
                }

                // 回调拆分
                [$controller, $action] = $route['callable'];

                // 参数验证
                if (isset($route['validate']) && method_exists($route['validate'], $action)) {
                    $complex = $route['validate']->$action();
                    $req->params = $complex->check(array_merge(
                        $req->get ?? [], $req->post ?? []
                    ));
                }

                // 中间件 + 用户操作
                $callback = array_reduce(array_reverse($route['middlewares']), function($next, $class) use($req, $res){
                    return function() use($class, $req, $next) {
                        return (new $class)->handle($req, $next);
                    };
                }, fn() => $controller->$action($req, $res));

                // 保存控制器返回的结果
                $result['data'] = $callback();

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
            if ($res->isWritable()) {
                $res->status(200);
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode($result));
            }
        }) > 0;
    }

    /**
     * Http - 后置事件
     */
    public function onHttpAfter($req, $res) : bool
    {
        // 返回结果
        return true;
    }
}