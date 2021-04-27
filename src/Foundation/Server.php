<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Closure;
use Throwable;
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
        $config = $this->config[$this->token] ?? $this->getDefaultConfig();

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

        foreach ([
            \Minimal\Listeners\Http\OnHttpBefore::class,
            \Minimal\Listeners\Http\OnHttp::class,
            \Minimal\Listeners\Http\OnHttpAfter::class,
        ] as $listener) {
            $this->app->event->bind($listener);
        }

        $config = $this->getConfig();

        $this->handle = new $config['class'](...array_values($config['constructor']));
        $this->handle->set($config['settings'] ?? []);

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
     * 获取默认配置
     */
    public function getDefaultConfig() : array
    {
        return [
            'class'                     =>  \Swoole\WebSocket\Server::class,
            'constructor'               =>  [
                'host'                  =>  '0.0.0.0',
                'port'                  =>  8080,
            ],
            'hook_flags'                =>  null,
            'settings'                  =>  [
                'worker_num'            =>  swoole_cpu_num(),
                'task_worker_num'       =>  swoole_cpu_num(),
                'task_enable_coroutine' =>  true,
                'daemonize'             =>  true,
                'log_file'              =>  '../runtime/log/swoole.log',
                'pid_file'              =>  '../runtime/pid',
                'reload_async'          =>  true,
                'enable_coroutine'      =>  true,
                'stats_file'            =>  '../runtime/status',
                'document_root'         =>  '../public',
                'enable_static_handler' =>  true,
            ],
            'callbacks'                 =>  [],
        ];
    }

    /**
     * 获取服务器事件
     */
    public function defaultServerEvents() : array
    {
        return [
            \Swoole\Server::class   =>  [
                'OnStart'             =>  ['Server:OnStart',          \Minimal\Listeners\Server\OnStart::class],
                'OnShutdown'          =>  ['Server:OnShutdown',       \Minimal\Listeners\Server\OnShutdown::class],
                'OnWorkerStart'       =>  ['Server:OnWorkerStart',    \Minimal\Listeners\Server\OnWorkerStart::class],
                'OnWorkerStop'        =>  ['Server:OnWorkerStop',     \Minimal\Listeners\Server\OnWorkerStop::class],
                'OnWorkerExit'        =>  ['Server:OnWorkerExit',     \Minimal\Listeners\Server\OnWorkerExit::class],
                'OnConnect'           =>  ['Server:OnConnect',        \Minimal\Listeners\Server\OnConnect::class],
                'OnReceive'           =>  ['Server:OnReceive',        \Minimal\Listeners\Server\OnReceive::class],
                'OnPacket'            =>  ['Server:OnPacket',         \Minimal\Listeners\Server\OnPacket::class],
                'OnClose'             =>  ['Server:OnClose',          \Minimal\Listeners\Server\OnClose::class],
                'OnTask'              =>  ['Server:OnTask',           \Minimal\Listeners\Server\OnTask::class],
                'OnFinish'            =>  ['Server:OnFinish',         \Minimal\Listeners\Server\OnFinish::class],
                'OnPipeMessage'       =>  ['Server:OnPipeMessage',    \Minimal\Listeners\Server\OnPipeMessage::class],
                'OnWorkerError'       =>  ['Server:OnWorkerError',    \Minimal\Listeners\Server\OnWorkerError::class],
                'OnManagerStart'      =>  ['Server:OnManagerStart',   \Minimal\Listeners\Server\OnManagerStart::class],
                'OnManagerStop'       =>  ['Server:OnManagerStop',    \Minimal\Listeners\Server\OnManagerStop::class],
                'OnBeforeReload'      =>  ['Server:OnBeforeReload',   \Minimal\Listeners\Server\OnBeforeReload::class],
                'OnAfterReload'       =>  ['Server:OnAfterReload',    \Minimal\Listeners\Server\OnAfterReload::class],
            ],
            \Swoole\Http\Server::class  =>  [
                'OnRequest'           =>  ['Server:OnRequest',        \Minimal\Listeners\Http\OnRequest::class],
            ],
            \Swoole\WebSocket\Server::class     =>  [
                'OnHandShake'         =>  ['Server:OnHandShake',      \Minimal\Listeners\WebSocket\OnHandShake::class],
                'OnMessage'           =>  ['Server:OnMessage',        \Minimal\Listeners\WebSocket\OnMessage::class],
                'OnOpen'              =>  ['Server:OnOpen',           \Minimal\Listeners\WebSocket\OnOpen::class],
                'OnRequest'           =>  ['Server:OnRequest',        \Minimal\Listeners\WebSocket\OnRequest::class],
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
        foreach ($this->defaultServerEvents() as $swooleEvent => $bindings) {
            $events = array_merge($events, $bindings);
        }
        $events = array_merge($events, $config['callbacks'] ?? []);

        foreach ($events as $evName => $bindings) {
            $callback = $bindings;
            if (is_array($callback)) {
                if (2 !== count($bindings)) {
                    throw new Exception('Server [' . $evName . '] event bind fail');
                }
                $callback = function(...$arguments) use($bindings){
                    $this->app->event->trigger($bindings[0], $arguments);
                };

                $this->app->event->bind($bindings[1]);
            }

            $sevName = false === stripos($evName, 'on') ? $evName : substr($evName, 2);
            $this->handle->on($sevName, $callback);
        }
    }





    /**
     * 未定义方法
     */
    public function __call(string $method, array $arguments)
    {
        $this->app->log->debug($method, $arguments);
        return $this->handle->$method(...$arguments);
    }
}