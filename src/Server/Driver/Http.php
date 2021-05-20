<?php
declare(strict_types=1);

namespace Minimal\Server\Driver;

use Swoole\Process;
use Minimal\Application;
use Minimal\Contracts\Server;
use Minimal\Foundation\Exception;

/**
 * Http服务器类
 */
class Http implements Server
{
    /**
     * 系统配置
     */
    protected array $config = [];

    /**
     * 默认事件
     */
    protected array $events = [
        \Swoole\Server::class   =>  [
            'OnStart'             =>  ['Server:OnStart',          \Minimal\Server\Listener\Server\OnStart::class],
            'OnShutdown'          =>  ['Server:OnShutdown',       \Minimal\Server\Listener\Server\OnShutdown::class],
            'OnWorkerStart'       =>  ['Server:OnWorkerStart',    \Minimal\Server\Listener\Server\OnWorkerStart::class],
            'OnWorkerStop'        =>  ['Server:OnWorkerStop',     \Minimal\Server\Listener\Server\OnWorkerStop::class],
            'OnWorkerExit'        =>  ['Server:OnWorkerExit',     \Minimal\Server\Listener\Server\OnWorkerExit::class],
            'OnConnect'           =>  ['Server:OnConnect',        \Minimal\Server\Listener\Server\OnConnect::class],
            'OnReceive'           =>  ['Server:OnReceive',        \Minimal\Server\Listener\Server\OnReceive::class],
            'OnPacket'            =>  ['Server:OnPacket',         \Minimal\Server\Listener\Server\OnPacket::class],
            'OnClose'             =>  ['Server:OnClose',          \Minimal\Server\Listener\Server\OnClose::class],
            'OnTask'              =>  ['Server:OnTask',           \Minimal\Server\Listener\Server\OnTask::class],
            'OnFinish'            =>  ['Server:OnFinish',         \Minimal\Server\Listener\Server\OnFinish::class],
            'OnPipeMessage'       =>  ['Server:OnPipeMessage',    \Minimal\Server\Listener\Server\OnPipeMessage::class],
            'OnWorkerError'       =>  ['Server:OnWorkerError',    \Minimal\Server\Listener\Server\OnWorkerError::class],
            'OnManagerStart'      =>  ['Server:OnManagerStart',   \Minimal\Server\Listener\Server\OnManagerStart::class],
            'OnManagerStop'       =>  ['Server:OnManagerStop',    \Minimal\Server\Listener\Server\OnManagerStop::class],
            'OnBeforeReload'      =>  ['Server:OnBeforeReload',   \Minimal\Server\Listener\Server\OnBeforeReload::class],
            'OnAfterReload'       =>  ['Server:OnAfterReload',    \Minimal\Server\Listener\Server\OnAfterReload::class],
        ],
        \Swoole\Http\Server::class  =>  [
            'OnRequest'           =>  ['Server:OnRequest',        \Minimal\Server\Listener\Http\OnRequest::class],
        ],
        \Swoole\WebSocket\Server::class     =>  [
            'OnHandShake'         =>  ['Server:OnHandShake',      \Minimal\Server\Listener\WebSocket\OnHandShake::class],
            'OnMessage'           =>  ['Server:OnMessage',        \Minimal\Server\Listener\WebSocket\OnMessage::class],
            'OnOpen'              =>  ['Server:OnOpen',           \Minimal\Server\Listener\WebSocket\OnOpen::class],
            'OnRequest'           =>  ['Server:OnRequest',        \Minimal\Server\Listener\WebSocket\OnRequest::class],
        ],
    ];

    /**
     * 当前服务器句柄
     */
    protected mixed $handle;

    /**
     * 当前服务器进程
     */
    protected int $processId;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        $this->loadConfig($app->config->get('server', []));
    }

    /**
     * 载入配置
     */
    public function loadConfig(array $config) : static
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);

        if (isset($this->config['settings']['log_file'])) {
            $this->config['settings']['log_file'] = $this->app->configPath($this->config['settings']['log_file']);
        } else {
            $this->config['settings']['log_file'] = $this->app->logPath('server.log');
        }
        if (!is_dir($logFileDir = dirname($this->config['settings']['log_file']))) {
            mkdir($logFileDir, 0777, true);
        }

        if (isset($this->config['settings']['pid_file'])) {
            $this->config['settings']['pid_file'] = $this->app->configPath($this->config['settings']['pid_file']);
        } else {
            $this->config['settings']['pid_file'] = $this->app->runtimePath('server.pid');
        }
        if (!is_dir($pidFileDir = dirname($this->config['settings']['pid_file']))) {
            mkdir($pidFileDir, 0777, true);
        }

        if (isset($this->config['settings']['stats_file'])) {
            $this->config['settings']['stats_file'] = $this->app->configPath($this->config['settings']['stats_file']);
        } else {
            $this->config['settings']['stats_file'] = $this->app->runtimePath('server.status');
        }
        if (!is_dir($statsFileDir = dirname($this->config['settings']['stats_file']))) {
            mkdir($statsFileDir, 0777, true);
        }

        if (isset($this->config['settings']['document_root'])) {
            $this->config['settings']['document_root'] = $this->app->configPath($this->config['settings']['document_root']);
            if (!is_dir($publicDir = dirname($this->config['settings']['document_root']))) {
                mkdir($publicDir, 0777, true);
            }
        }

        return $this;
    }

    /**
     * 设置配置
     */
    public function setConfig(string|int $key, mixed $value) : static
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * 获取配置
     */
    public function getConfig(string $key = null) : mixed
    {
        return isset($key) ? ($this->config[$key] ?? null) : ($this->config ?? []);
    }

    /**
     * 获取默认配置
     */
    public function getDefaultConfig() : array
    {
        return [
            'class'                     =>  \Swoole\Http\Server::class,
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
            ],
            'callbacks'                 =>  [],
        ];
    }

    /**
     * 启动服务
     */
    public function start() : bool
    {
        // 检查状态
        if ($this->status()) {
            throw new Exception('The server has started');
        }

        // 绑定内置事件
        foreach ([
            \Minimal\Server\Listener\Http\OnHttpBefore::class,
            \Minimal\Server\Listener\Http\OnHttp::class,
            \Minimal\Server\Listener\Http\OnHttpAfter::class,
        ] as $listener) {
            $this->app->event->bind($listener);
        }

        // 获取配置
        $config = $this->getConfig();

        // 实例化服务器
        $this->handle = new $config['class'](...array_values($config['constructor']));
        $this->handle->set($config['settings'] ?? []);

        // 一键协程化
        \Swoole\Runtime::enableCoroutine($config['flags'] ?? SWOOLE_HOOK_ALL);

        // 绑定服务器事件
        $this->bindServerEvents();

        // 开启服务器
        return $this->handle->start();
    }

    /**
     * 停止服务
     */
    public function stop() : bool
    {
        // 检查状态
        if (!$this->status()) {
            throw new Exception('The server not already running');
        }

        // 循环关闭
        $count = 0;
        while($exist = Process::kill($this->processId, 0)) {
            $count >= 20 ? Process::kill($this->processId, SIGKILL) : Process::kill($this->processId);
            $count++;
            usleep(500000);     // 1微秒（micro second）是百万分之一秒。
        }

        // 返回结果
        return true;
    }

    /**
     * 重载服务
     */
    public function reload() : bool
    {
        // 检查状态
        if (!$this->status()) {
            throw new Exception('The server not already running');
        }

        // 重载并返回结果
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
        // 获取配置
        $config = $this->getConfig();

        // 获取PID文件
        $file = $config['settings']['pid_file'];
        clearstatcache(true, $file);
        if (!is_file($file)) {
            return false;
        }

        // 获取状态
        $pid = file_get_contents($file);
        $pid = $pid ? (int) $pid: 0;
        if (!Process::kill($pid, 0)) {
            return false;
        }

        // 保存进程编号
        $this->processId = $pid;

        // 返回结果
        return true;
    }

    /**
     * 绑定服务器事件
     */
    public function bindServerEvents() : void
    {
        // 获取配置
        $config = $this->getConfig();

        // 循环合并服务器事件
        $events = [];
        foreach ($this->events as $serverClass => $bindings) {
            $events = array_merge($events, $bindings);
        }
        // 合并用户事件
        $events = array_merge($events, $config['callbacks'] ?? []);
        // 循环绑定事件
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
     * 未知函数
     */
    public function __call(string $method, array $parameters) : mixed
    {
        return $this->handle->$method(...$parameters);
    }
}