<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Closure;
use Throwable;
use Exception;
use Swoole\Process;
use Minimal\Application;
use Minimal\Contracts\Listener;
use Minimal\Contracts\Server AS ServerInterface;

/**
 * 服务器类
 */
class Server implements ServerInterface
{
    /**
     * 当前服务器
     */
    protected string $token = 'default';

    /**
     * 系统配置
     */
    protected array $config;

    /**
     * 当前服务器句柄
     */
    protected $handle;

    /**
     * 当前服务器进程
     */
    protected int $processId;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 设置配置
     */
    public function setConfig(array $config) : static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * 获取配置
     */
    public function getConfig() : array
    {
        $config = $this->config[$this->token] ?? [];

        if (isset($config['settings']['log_file'])) {
            $config['settings']['log_file'] = $this->app->configPath($config['settings']['log_file']);
        } else {
            $config['settings']['log_file'] = $this->app->logPath('server.log');
        }
        if (!is_dir($logFileDir = dirname($config['settings']['log_file']))) {
            mkdir($logFileDir, 0777, true);
        }

        if (isset($config['settings']['pid_file'])) {
            $config['settings']['pid_file'] = $this->app->configPath($config['settings']['pid_file']);
        } else {
            $config['settings']['pid_file'] = $this->app->runtimePath('server.pid');
        }
        if (!is_dir($pidFileDir = dirname($config['settings']['pid_file']))) {
            mkdir($pidFileDir, 0777, true);
        }

        if (isset($config['settings']['stats_file'])) {
            $config['settings']['stats_file'] = $this->app->configPath($config['settings']['stats_file']);
        } else {
            $config['settings']['stats_file'] = $this->app->runtimePath('server.status');
        }
        if (!is_dir($statsFileDir = dirname($config['settings']['stats_file']))) {
            mkdir($statsFileDir, 0777, true);
        }

        if (isset($config['settings']['document_root'])) {
            $config['settings']['document_root'] = $this->app->configPath($config['settings']['document_root']);
        } else {
            $config['settings']['document_root'] = $this->app->basePath('public');
        }

        return $config;
    }

    /**
     * 切换服务
     */
    public function use(string $name) : static
    {
        $this->token = $name;

        if (!isset($this->config[$name])) {
            throw new Exception('server config [' . $name . '] not found');
        }

        return $this;
    }

    /**
     * 启动服务
     */
    public function start() : bool
    {
        if ($this->status()) {
            throw new Exception('The server has started');
        }

        $config = $this->getConfig();

        $this->handle = new $config['class'](...array_values($config['constructor']));
        $this->handle->set($config['settings'] ?? []);

        foreach ([
            'onHttpBefore'                  =>  [$this, 'onHttpBefore'],
            'onHttp'                        =>  [$this, 'onHttp'],
            'onHttpAfter'                   =>  [$this, 'onHttpAfter'],
        ] as $evName => $callback) {
            $this->app->event->on($evName, $callback);
        }

        \Swoole\Runtime::enableCoroutine($config['flags'] ?? SWOOLE_HOOK_ALL);

        $this->bindServerEvents();

        return $this->handle->start();
    }

    /**
     * 停止服务
     */
    public function stop() : bool
    {
        if (!$this->status()) {
            throw new Exception('The server not already running');
        }

        $count = 0;
        while($exist = Process::kill($this->processId, 0)) {
            $count >= 20 ? Process::kill($this->processId, SIGKILL) : Process::kill($this->processId);
            $count++;
            usleep(500000);     // 1微秒（micro second）是百万分之一秒。
        }

        return true;
    }

    /**
     * 重载服务
     */
    public function reload() : bool
    {
        if (!$this->status()) {
            throw new Exception('The server not already running');
        }

        return Process::kill($this->processId, SIGUSR1);
    }

    /**
     * 重启服务
     */
    public function restart() : bool
    {
        if ($this->status()) {
            $this->stop();
        }

        return $this->start();
    }

    /**
     * 服务状态
     */
    public function status() : bool
    {
        $config = $this->getConfig();

        $file = $config['settings']['pid_file'];
        clearstatcache(true, $file);
        if (!is_file($file)) {
            return false;
        }

        $pid = file_get_contents($file);
        $pid = $pid ? (int) $pid: 0;
        if (!Process::kill($pid, 0)) {
            return false;
        }

        $this->processId = $pid;

        return true;
    }




    /**
     * 获取服务器事件
     */
    public function defaultServerEvents() : array
    {
        return [
            \Swoole\Server::class               =>  [
                'onStart'                       =>  [$this, 'onStart'],
                'onManagerStart'                =>  [$this, 'onManagerStart'],
                'onWorkerStart'                 =>  [$this, 'onWorkerStart'],
                'onWorkerError'                 =>  [$this, 'onWorkerError'],
                'onWorkerExit'                  =>  [$this, 'onWorkerExit'],
                'onTask'                        =>  [$this, 'onTask'],
            ],
            \Swoole\Http\Server::class     =>  [
                'onRequest'                     =>  [$this, 'onRequest'],
            ],
            \Swoole\WebSocket\Server::class     =>  [
                'onMessage'                     =>  [$this, 'onMessage'],
            ],
        ];
    }

    /**
     * 监听事件
     */
    public function bindServerEvents() : void
    {
        $config = $this->getConfig();

        $events = [];
        foreach ($this->defaultServerEvents() as $interface => $bindings) {
            if ($this->handle instanceof $interface) {
                $events = array_merge($events, $bindings);
            }
        }
        $events = array_merge($events, $config['callbacks'] ?? []);

        foreach ($events as $evName => $callback) {
            $this->app->event->on($evName, $callback);

            if (!is_callable($callback) && (!is_array($callback) || (is_array($callback) && count($callback) != 2))) {
                throw new Exception('Server [' . $evName . '] event bind fail');
            }
            if (is_array($callback) && is_string($callback[0])) {
                $callback[0] = $this->app->get($callback[0]);
            }

            $sevName = false === stripos($evName, 'on') ? $evName : substr($evName, 2);
            $this->handle->on($sevName, $callback);
        }
    }




    /**
     * 启动后在主进程（master）的主线程回调此函数
     */
    public function onStart(\Swoole\Server $server) : bool
    {
        cli_set_process_title('php swoole master');

        return true;
    }

    /**
     * 当管理进程启动时触发此事件
     */
    public function onManagerStart(\Swoole\Server $server) : bool
    {
        cli_set_process_title('php swoole manager');

        return true;
    }

    /**
     * 此事件在 Worker 进程 / Task 进程 启动时发生，这里创建的对象可以在进程生命周期内使用。
     */
    public function onWorkerStart(\Swoole\Server $server, int $workerId) : bool
    {
        cli_set_process_title(sprintf('php swoole %s worker #%s', $server->taskworker ? 'task' : 'normal', $workerId));

        return true;
    }

    /**
     * 当 Worker/Task 进程发生异常后会在 Manager 进程内回调此函数。
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal) : bool
    {
        $this->app->log->error($worker_id . ':' . $exit_code . ':' . $signal, error_get_last());
        return true;
    }

    /**
     * 仅在开启 reload_async 特性后有效。参见 如何正确的重启服务
     */
    public function onWorkerExit(\Swoole\Server $server, int $workerId) : bool
    {
        \Swoole\Timer::clearAll();

        return true;
    }

    /**
     * 在 task 进程内被调用。
     * worker 进程可以使用 task 函数向 task_worker 进程投递新的任务。
     * 当前的 Task 进程在调用 onTask 回调函数时会将进程状态切换为忙碌，
     * 这时将不再接收新的 Task，
     * 当 onTask 函数返回时会将进程状态切换为空闲然后继续接收新的 Task。
     */
    public function onTask(\Swoole\Server $server, int $task_id, int $src_worker_id, mixed $data) : bool
    {
        /**
         *
         * Swoole\Server\Task Object
         *   (
         *       [data] => OnGameNext
         *       [dispatch_time] => 1612700724.3543
         *       [id] => 4
         *       [worker_id] => 0
         *       [flags] => 132
         *   )
         */

        return true;
    }

    /**
     * 在收到一个完整的 HTTP 请求后，会回调此函数
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response) : bool
    {
        // 前置事件
        $bool = $this->app->event->trigger('onHttpBefore', [$request, $response]);
        if (false === $bool) {
            return false;
        }

        // 请求处理
        $bool = $this->app->event->trigger('onHttp', [$request, $response]);
        if (false === $bool) {
            return false;
        }

        // 后置事件
        return $this->app->event->trigger('onHttpAfter', [$request, $response]);;
    }

    /**
     * Http - 前置事件
     */
    public function onHttpBefore(string $method, array $arguments) : bool
    {
        // 获取参数
        [$request, $response] = $arguments;

        // Favicon
        if (($request->server['request_uri'] ?? $request->server['path_info']) == '/favicon.ico') {
            $response->end();
            return false;
        }

        // 返回结果
        return true;
    }

    /**
     * Http - 请求处理
     */
    public function onHttp(string $method, array $arguments) : bool
    {

        // 获取参数
        [$request, $response] = $arguments;

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
                if (is_array($route['callback']) && 2 === count($route['callback']) && is_string($route['callback'][0])) {
                    $route['callback'][0] = $this->app->make($route['callback'][0]);
                }

                // 回调拆分
                [$controller, $action] = $route['callback'];

                // 中间件 + 用户操作
                $callback = array_reduce(array_reverse($route['middlewares'] ?? []), function($next, $class) use($request, $response){
                    return function() use($class, $request, $next) {
                        return (new $class)->handle($request, $next);
                    };
                }, fn() => $controller->$action($request, $response));

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
            if ($response->isWritable()) {
                $response->status(200);
                $response->header('Content-Type', 'application/json;charset=utf-8');
                $response->end(json_encode($result));
            }
        }) > 0;
    }

    /**
     * Http - 后置事件
     */
    public function onHttpAfter(string $method, array $arguments) : bool
    {
        // 获取参数
        [$request, $response] = $arguments;

        // 返回结果
        return true;
    }

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数。
     */
    public function onMessage(\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) : bool
    {
        return true;
    }
}