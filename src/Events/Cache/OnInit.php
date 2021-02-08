<?php
declare(strict_types=1);

namespace Minimal\Events\Cache;

use Minimal\Config;
use Minimal\Container\Container;
use Minimal\Cache\Manager as Cache;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 缓存 - 初始化事件
 * worker进程
 */
#[Listener]
class OnInit implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected Config $config)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Cache:OnInit', 'Server:OnWorkerStart'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        if ($event == 'Server:OnWorkerStart') {
            // 普通或任务Worker进程进入

            // Server对象
            $server = $arguments[0];
            // 缓存对象
            $configs = $this->config->get('cache', []);
            if ($server->taskworker) {
                $configs['pool']['master'] = $server->setting['task_worker_num'];
                $configs['pool']['slave'] = 0;
                $configs['worker_num'] = $server->setting['task_worker_num'];
            } else {
                $configs['worker_num'] = $server->setting['worker_num'];
            }
            $this->container->set('cache', new Cache($configs));

        } else {
            // 用户自定义事件 - 只需一个连接

            // 缓存对象
            $configs = $this->config->get('cache', []);
            $configs['pool']['master'] = 1;
            $configs['pool']['slave'] = 0;
            $configs['worker_num'] = 1;
            $this->container->set('cache', new Cache($configs));

        }

        // 返回结果
        return true;
    }
}