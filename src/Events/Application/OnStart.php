<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Http\Server;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 启动事件
 */
#[Listener]
class OnStart implements ListenerInterface
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
        return ['Application:OnStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        $server = new Server('0.0.0.0', 80);
        $server->set(array_merge([
            'worker_num'    =>  swoole_cpu_num(),
            'reload_async'  =>  true,
            'max_wait_time' =>  60,
        ], $arguments));
        foreach([
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
        ] as $swooleEvent => $minimalEvent) {
            $server->on($swooleEvent, function(...$arguments) use($minimalEvent) {
                $this->app->trigger($minimalEvent, $arguments);
            });
        };
        $this->app->trigger('Server:OnInit', $server);
        return $server->start();
    }
}