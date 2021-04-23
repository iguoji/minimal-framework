<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

use Throwable;
use Minimal\Application;
use Minimal\Contracts\Listener;

/**
 * 应用重载事件
 */
class OnReload implements Listener
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
            'Application:OnReload',
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
        // 重载服务器
        $server->reload();

        // 返回结果
        return true;
    }
}