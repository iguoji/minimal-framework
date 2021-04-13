<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Foundation\Config;
use Minimal\Contracts\Service;

/**
 * 配置文件服务类
 */
class ConfigService implements Service
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
        $files = glob($this->app->configPath('*.php'));
        $data = [];
        foreach ($files as $key => $file) {
            $data[pathinfo($file, PATHINFO_FILENAME)] = require $file;
        }
        $this->app->set('config', new Config($data));
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {

    }
}