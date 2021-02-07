<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Server;
use Swoole\Coroutine;
use Minimal\Config;
use Minimal\Container\Container;
use Minimal\Cache\Manager as Cache;
use Minimal\Database\Manager as Database;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 启动事件
 * worker进程
 */
#[Listener]
class OnLaunch implements ListenerInterface
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
        return ['Application:OnLaunch'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        if (isset($arguments[0]) && $arguments[0] instanceof Server) {
            // worker进程方可进入

            // 数据库对象
            $configs = $this->config->get('db', []);
            if ($arguments[0]->taskworker) {
                $configs['pool']['master'] = $arguments[0]->setting['task_worker_num'];
                $configs['pool']['slave'] = 0;
                $configs['worker_num'] = $arguments[0]->setting['task_worker_num'];
            } else {
                $configs['worker_num'] = $arguments[0]->setting['worker_num'];
            }
            $this->container->set('db', new Database($configs));

            // 缓存对象
            $configs = $this->config->get('cache', []);
            if ($arguments[0]->taskworker) {
                $configs['pool']['master'] = $arguments[0]->setting['task_worker_num'];
                $configs['pool']['slave'] = 0;
                $configs['worker_num'] = $arguments[0]->setting['task_worker_num'];
            } else {
                $configs['worker_num'] = $arguments[0]->setting['worker_num'];
            }
            $this->container->set('cache', new Cache($configs));

        } else {

            // 数据库对象
            $configs = $this->config->get('db', []);
            $configs['pool']['master'] = 1;
            $configs['pool']['slave'] = 0;
            $configs['worker_num'] = 1;
            $this->container->set('db', new Database($configs));

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