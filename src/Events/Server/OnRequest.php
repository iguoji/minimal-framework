<?php
declare(strict_types=1);

namespace Minimal\Events\Server;

use Minimal\Application;
use Minimal\Container\Container;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当 HTTP 服务器收到来自客户端的数据时会回调此函数。
 */
#[Listener]
class OnRequest implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected Application $app)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnRequest'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 保存请求
        $this->container->set($arguments[0]::class, $arguments[0]);
        $this->container->setAlias('request', $arguments[0]::class);

        // 前置事件
        $bool = $this->app->trigger('Application:OnRequestBefore', $arguments);
        if (false === $bool) {
            return false;
        }

        // 处理请求
        $bool = $this->app->trigger('Application:OnRequest', $arguments);
        if (false === $bool) {
            return false;
        }

        // 后置事件
        return $this->app->trigger('Application:OnRequestAfter', $arguments);
    }
}