<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 当管理进程结束时触发
 */
class OnManagerStop implements Listener
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
            'Server:OnManagerStop',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // Swoole\Server
        $server = $arguments[0];

        // 返回结果
        return true;
    }
}