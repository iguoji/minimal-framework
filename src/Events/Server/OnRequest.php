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
        // 处理请求
        Coroutine::create(function() use($req, $res){
            // 控制输出
            ob_start();
            try {
                // 触发事件
                $this->app->trigger('Server:OnRequestBefore', [$req, $res]);
                // 域名主机
                $host = $req->header['host'];
                // 请求地址
                $path = $req->server['request_uri'] ?? $req->server['path_info'];
                // favicon
                if ($path == '/favicon.ico') {
                    $res->end();
                    return;
                }
                // 匹配路由
                $route = $this->app->getRoute($path, $host);
                if (is_null($route)) {
                    throw new RuntimeException('api not found');
                }
                if (!in_array(strtoupper($req->server['request_method']), $route['methods'])) {
                    var_dump($req->server['request_method'], $route['methods']);
                    throw new RuntimeException('method not allowed');
                }
                // 中间件
                $next = fn() => $res;
                $callback = array_reduce(array_reverse($route['middlewares']), function($next, $class) use ($req, $res){
                    return function() use($req, $res, $next, $class){
                        return (new $class)->handle($req, $res, $next);
                    };
                }, $next);
                $callback();
                // 回调拆分
                [$controller, $action] = $route['callable'];
                // 验证器
                if (isset($route['validate']) && method_exists($route['validate'], $action)) {
                    $route['validate']->$action($req);
                }
                // 调用控制器
                $result = $controller->$action($req, $res);
                // 触发事件
                $this->app->trigger('Server:OnRequestAfter', [$req, $res, $result]);
                // 输出结果
                $res->status(200);
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode([
                    'code'      =>  200,
                    'message'   =>  'success',
                    'data'      =>  $result,
                ]));
            } catch (Throwable $th) {
                // 触发事件
                $this->app->trigger('Server:OnRequestAfter', [$req, $res, [], $th]);
                // 记录日志
                $logContext = ['exception' => $th];
                if (method_exists($th, 'getData')) {
                    $logContext = array_merge($logContext, $th->getData());
                }
                // $this->log->error($th->getMessage(), $logContext);
                // 打印错误
                $res->status(200);
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode([
                    'code'      =>  $th->getCode() ?: 500,
                    'message'   =>  $th->getMessage(),
                    'data'      =>  method_exists($th, 'getData') ? $th->getData() : [],
                ]));
            }
            // 刷新输出
            ob_end_flush();
        });
        // 继续执行
        return true;
    }
}