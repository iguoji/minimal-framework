<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 启动后在主进程（master）的主线程回调此函数
 */
class OnStart implements Listener
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
            'Server:OnStart',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];

        // process
        cli_set_process_title('php swoole master');

        // 返回结果
        return true;
    }
}