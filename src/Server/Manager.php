<?php
declare(strict_types=1);

namespace Minimal\Server;

use Minimal\Application;
use Minimal\Support\Manager AS ManagerInterface;

/**
 * 服务器管理类
 */
class Manager extends ManagerInterface
{
    /**
     * 别名集合
     */
    protected array $aliases = [
        'cache'     =>  \Minimal\Cache\Manager::class,
        'database'  =>  \Minimal\Database\Manager::class,

        'queue'     =>  \Minimal\Server\Queue::class,
        'route'     =>  \Minimal\Http\Route::class,
        'context'   =>  \Minimal\Http\Context::class,
        'cookie'    =>  \Minimal\Http\Cookie::class,
        'session'   =>  \Minimal\Http\Session::class,
        'request'   =>  \Minimal\Http\Request::class,
        'response'  =>  \Minimal\Http\Response::class,
        'view'      =>  \think\Template::class,
    ];

    /**
     * 事件集合
     */
    protected array $listeners = [
        \Minimal\Server\Listener\Cache::class,
        \Minimal\Server\Listener\Database::class,
    ];

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 绑定别名
        foreach ($this->aliases as $key => $value) {
            $app->setAlias($key, $value);
        }
        // 绑定实例
        $app->set('server', $this);
        // 绑定事件
        foreach ($this->listeners as $class) {
            $app->event->bind($class);
        }
    }

    /**
     * 默认驱动
     */
    public function getDefaultDriver() : string
    {
        return 'http';
    }

    /**
     * 创建Http驱动
     */
    public function createHttpDriver() : mixed
    {
        return $this->app->make(\Minimal\Server\Driver\Http::class);
    }
}