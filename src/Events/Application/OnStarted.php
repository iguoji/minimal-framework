<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 启动成功事件
 * 主进程
 */
#[Listener]
class OnStarted implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct()
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnStarted'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 返回结果
        return true;
    }
}