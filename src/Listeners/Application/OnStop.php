<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 应用停止事件
 */
class OnStop implements Listener
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
            'Application:OnStop',
        ];
    }

    /**
     * 程序处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 获取服务器
        $server = $this->app->server;
        // 保存配置
        $server->setConfig($this->app->config->get('server', []));
        // 切换服务器
        if (isset($arguments['target'])) {
            $server->use($arguments['target']);
        }
        // 停止服务器
        $server->stop();

        // 返回结果
        return true;
    }
}