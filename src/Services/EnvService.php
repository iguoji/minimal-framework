<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Foundation\Env;
use Minimal\Contracts\Service;

/**
 * 环境变量服务类
 */
class EnvService implements Service
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
        $file = $this->app->basePath('.env');
        $data = [];
        if (file_exists($file)) {
            $data = parse_ini_file($file, true);
        }
        $this->app->set('env', new Env($data));
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {

    }
}