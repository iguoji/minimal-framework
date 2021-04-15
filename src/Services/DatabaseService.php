<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Database\Manager;
use Minimal\Contracts\Service;

/**
 * 数据库服务类
 */
class DatabaseService implements Service
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
    {
        $config = $this->app->config->get('db', []);

        $this->app->set('database', new Manager($config));
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {

    }
}