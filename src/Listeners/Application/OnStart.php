<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

use Swoole\Http\Server;
use Minimal\Config;
use Minimal\Application;
use Minimal\Container\Container;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 启动事件
 * 主进程
 */
#[Listener]
class OnStart implements ListenerInterface
{
    /**
     * 事件列表
     */
    protected $events = [
        'Start'             =>  'Server:OnStart',
        'Shutdown'          =>  'Server:OnShutdown',
        'WorkerStart'       =>  'Server:OnWorkerStart',
        'WorkerStop'        =>  'Server:OnWorkerStop',
        'WorkerExit'        =>  'Server:OnWorkerExit',
        'Request'           =>  'Server:OnRequest',
        'Connect'           =>  'Server:OnConnect',
        'Receive'           =>  'Server:OnReceive',
        'Packet'            =>  'Server:OnPacket',
        'Close'             =>  'Server:OnClose',
        'HandShake'         =>  'Server:OnHandShake',
        'Open'              =>  'Server:OnOpen',
        'Message'           =>  'Server:OnMessage',
        'Task'              =>  'Server:OnTask',
        'Finish'            =>  'Server:OnFinish',
        'PipeMessage'       =>  'Server:OnPipeMessage',
        'WorkerError'       =>  'Server:OnWorkerError',
        'ManagerStart'      =>  'Server:OnManagerStart',
        'ManagerStop'       =>  'Server:OnManagerStop',
        'BeforeReload'      =>  'Server:OnBeforeReload',
        'AfterReload'       =>  'Server:OnAfterReload',
    ];

    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected Application $app, protected Config $config)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 获取配置
        $config = $this->config->get('server');

        // 基础目录
        $appContext = $this->app->getContext();

        // 运行状态
        $pid = OnStatus::running($appContext['runtimePath'], $config);
        if (false !== $pid) {
            return false;
        }

        // 服务实例
        $server = new Server(
            $config['ip'] ?? '0.0.0.0',
            $config['port'] ?? 9501,
        );

        // 保存服务
        $this->container->set(Server::class, $server);
        $this->container->setAlias('server', Server::class);

        // 日志文件
        // 在服务器程序运行期间日志文件被 mv 移动或 unlink 删除后，
        // 日志信息将无法正常写入，
        // 这时可以向 Server 发送 SIGRTMIN 信号实现重新打开日志文件。
        if (!is_dir($appContext['logPath']) && !mkdir($appContext['logPath'], 0777, true)) {
            echo '很抱歉、无法创建日志文件夹！', PHP_EOL;
            return false;
        }

        // 配置选项
        $server->set(array_merge([
            'worker_num'    =>  swoole_cpu_num(),
            'reload_async'  =>  true,
            'max_wait_time' =>  60,
            'daemonize'     =>  true,
            'log_file'      =>  $appContext['logPath'] . 'app.log',
            'log_rotation'  =>  SWOOLE_LOG_ROTATION_DAILY,
            'pid_file'      =>  $appContext['runtimePath'] . 'pid',
            'task_enable_coroutine' =>  true,
        ], $config['settings'] ?? [], $arguments));

        // 循环注册事件
        foreach($this->events as $swooleEvent => $minimalEvent) {
            // 注册Swoole事件
            // ...$arguments = Swoole提供的参数
            $server->on($swooleEvent, function(...$arguments) use($minimalEvent) {

                // Swoole回调来了，立即触发Minimal事件
                // $arguments = Swoole事件的参数列表数组
                // 但无法...解包传递过去，因为事件对象实现了接口，参数必须统一，但每个回调参数却又不一样
                $this->app->trigger($minimalEvent, $arguments);

            });
        };

        // 成功提示
        echo sprintf(
            'Server running on %s:%s at %s，Process id: %s',
            $config['ip'] ?? '0.0.0.0',
            $config['port'] ?? 9501,
            date('Y-m-d H:i:s'),
            $server->getMasterPid()
        ), PHP_EOL;

        // 触发事件
        $this->app->trigger('Application:OnStarted');

        // 启动服务
        $bool = $server->start();
        if (!$bool) {
            echo '很抱歉、服务器启动失败！', PHP_EOL;
        }

        // 返回结果
        return $bool;
    }
}