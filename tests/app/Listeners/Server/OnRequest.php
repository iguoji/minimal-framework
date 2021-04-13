<?php
declare(strict_types=1);

namespace App\Listeners\Server;

use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 应用程序 - 请求事件
 */
class OnRequest implements Listener
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
        return ['Application:OnRequestBefore', 'Application:OnRequest', 'Application:OnRequestAfter'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 调试信息
        $this->app->log->debug($event, $arguments);
        // 返回结果
        return true;
    }
}