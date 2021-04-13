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
        // 路由配置
        $config = $this->app->config->get('route', []);
        $config['options'] = array_merge([
            'routeParser'       => \FastRoute\RouteParser\Std::class,
            'dataGenerator'     => \FastRoute\DataGenerator\GroupCountBased::class,
            'dispatcher'        => \FastRoute\Dispatcher\GroupCountBased::class,
            'routeCollector'    => \FastRoute\RouteCollector::class,
            'cacheDisabled'     => false,
            'cacheFile'         => '../runtime/cache/route.php',
        ], $config['options'] ?? []);

        // 缓存文件
        $config['options']['cacheFile'] = $this->app->configPath($config['options']['cacheFile']);

        // 路由对象
        $route = $this->app->make(\Minimal\Foundation\Route::class, $config, glob($this->app->routePath('*.php')));

        // 保存对象
        $this->app->set('route', $route);

        /*// 按情况判断是否使用缓存
        if (!$options['cacheDisabled'] && file_exists($options['cacheFile'])) {
            // 使用缓存
            $dispatchData = require $options['cacheFile'];
            if (!is_array($dispatchData)) {
                throw new RuntimeException('invalid route cache file "' . $options['cacheFile'] . '"');
            }
        } else {
            $routeCollector = new $options['routeCollector'](
                new $options['routeParser'], new $options['dataGenerator']
            );
            $routeFiles = glob($this->app->routePath('*.php'));
            foreach ($routeFiles as $routeFile) {
                $routeFunc = require $routeFile;
                if (!is_callable($routeFunc)) {
                    throw new RuntimeException('invalid route config file "' . $options['cacheFile'] . '"');
                }
                $routeFunc($routeCollector);
            }

            // 写入缓存
            $dispatchData = $routeCollector->getData();
            if (!$options['cacheDisabled']) {
                if (!is_dir(dirname($options['cacheFile']))) {
                    mkdir(dirname($options['cacheFile']), 0777, true);
                }
                file_put_contents(
                    $options['cacheFile'],
                    '<?php return ' . var_export($dispatchData, true) . ';'
                );
            }
        }

        $this->app->set('route', new $options['dispatcher']($dispatchData));*/
    }
}