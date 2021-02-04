<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Throwable;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当 HTTP 服务器收到来自客户端的数据时会回调此函数。
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
        return ['Server:OnRequest'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 请求对象
        $req = $arguments[0];
        // 响应对象
        $res = $arguments[1];

        // 前置事件
        $bool = $this->app->trigger('Application:OnRequestBefore', $arguments);
        if (false === $bool) {
            return true;
        }

        // 处理请求
        Coroutine::create(function() use($req, $res){
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
                $result = $callback();

                // 输出结果
                if ($res->isWritable()) {
                    $res->status(200);
                    $res->header('Content-Type', 'application/json;charset=utf-8');
                    $res->end(json_encode([
                        'code'      =>  200,
                        'message'   =>  '恭喜您、操作成功！',
                        'data'      =>  $result,
                    ]));
                }
            } catch (Throwable $th) {
                $res->status(200);
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode([
                    'code'      =>  $th->getCode() ?: 500,
                    'message'   =>  $th->getMessage(),
                    'file'      =>  $th->getFile(),
                    'line'      =>  $th->getLine(),
                    'data'      =>  method_exists($th, 'getData') ? $th->getData() : [],
                    'trace'     =>  $th->getTrace(),
                ]));
            }
        });

        // 后置事件
        $bool = $this->app->trigger('Application:OnRequestAfter', $arguments);
        if (false === $bool) {
            return true;
        }

        // 继续执行
        return true;
    }
}