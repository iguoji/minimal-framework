<?php
declare(strict_types=1);

namespace Minimal\Services;

use RuntimeException;
use Minimal\Application;
use Minimal\Contracts\Service;

/**
 * 路由服务类
 */
class RouteService implements Service
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 注册服务
     */
    public function register() : void
    {}

    /**
     * 启动服务
     */
    public function boot() : void
    {
        $callable = $this->app->config->get('route');
        if (is_callable($callable)) {
            $callable($this->app->route);
        }
    }
}