<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Http\Server;
use Minimal\Application;
use Minimal\Container\Container;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 启动事件
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
    public function __construct(protected Application $app, protected Container $container)
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
        // 服务实例
        $server = new Server(
            $this->container->config->get('server.ip', '0.0.0.0'),
            $this->container->config->get('server.port', 9501),
        );
        // 配置选项
        $server->set(array_merge([
            'worker_num'    =>  swoole_cpu_num(),
            'reload_async'  =>  true,
            'max_wait_time' =>  60,
        ], $this->container->config->get('server.setting', []), $arguments));
        // 循环事件
        foreach($this->events as $swooleEvent => $minimalEvent) {
            // 注册事件
            $server->on($swooleEvent, function(...$arguments) use($minimalEvent) {
                // 触发事件
                $this->app->trigger($minimalEvent, $arguments);
            });
        };
        // 启动服务
        return $server->start();
    }
}