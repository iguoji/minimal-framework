<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Cache\Manager;
use Minimal\Contracts\Service;

/**
 * 缓存服务类
 */
class CacheService implements Service
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
        $config = $this->app->config->get('cache', []);

        if (isset($config['file'])) {
            if (empty($config['file']['path'])) {
                $config['file']['path'] = $this->app->cachePath();
            } else {
                $config['file']['path'] = $this->app->configPath($config['file']['path']);
            }
            $config['file']['path'] .= DIRECTORY_SEPARATOR;
        }

        $this->app->set('cache', new Manager($config));
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {

    }
}