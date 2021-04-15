<?php
declare(strict_types=1);

namespace Minimal;

use Minimal\Foundation\Facade;
use Minimal\Foundation\Service;
use Minimal\Foundation\Container;

/**
 * 应用类
 */
class Application extends Container
{
    /**
     * 版本号码
     */
    const VERSION = '1.0.0';

    /**
     * 目录列表
     */
    protected string $basePath;         // 基础目录：  /
    protected string $appPath;          // 应用目录：  /app/
    protected string $configPath;       // 配置目录：  /config/
    protected string $routePath;        // 路由目录：  /config/route/
    protected string $runtimePath;      // 运行时目录：/runtime/
    protected string $logPath;          // 日志目录：  /runtime/logs/
    protected string $cachePath;        // 日志目录：  /runtime/cache/

    /**
     * 别名集合
     */
    protected array $aliases = [
        'service'   =>  \Minimal\Foundation\Service::class,
        'event'     =>  \Minimal\Foundation\Event::class,
        'server'    =>  \Minimal\Foundation\Server::class,
        'route'     =>  \Minimal\Route\Manager::class,
    ];

    /**
     * 服务集合
     */
    protected array $services = [
        'error'     =>  \Minimal\Services\ErrorService::class,
        'env'       =>  \Minimal\Services\EnvService::class,
        'config'    =>  \Minimal\Services\ConfigService::class,
        'log'       =>  \Minimal\Services\LoggerService::class,             // https://github.com/Seldaek/monolog
        'console'   =>  \Minimal\Services\ConsoleService::class,            // https://github.com/symfony/console
        'event'     =>  \Minimal\Services\EventService::class,
        'cache'     =>  \Minimal\Services\CacheService::class,
        'database'  =>  \Minimal\Services\DatabaseService::class,
        'route'     =>  \Minimal\Services\RouteService::class,              // https://github.com/nikic/FastRoute
    ];

    /**
     * 构造函数
     */
    public function __construct(string $basePath)
    {
        // 基础目录
        $this->useBasePath($basePath);
        // 绑定容器
        $this->set('app', $this);
        $this->set('container', $this);
        // 服务绑定
        $this->set('service', new Service($this, $this->services));
        // 门面注入
        Facade::setContainer($this);
    }

    /**
     * 执行命令
     */
    public function execute(array $argv = []) : void
    {
        // 注册服务
        $this->service->registerServices();
        // 启动服务
        $this->service->bootServices();

        // 监听命令
        $this->console->run();
    }




    /**
     * 规范目录
     */
    public function folder(string $path = null) : string
    {
        return empty($path) ? '' : (str_ends_with($path, DIRECTORY_SEPARATOR) ? $path : $path . DIRECTORY_SEPARATOR);
    }

    /**
     * 目录拼接
     */
    public function paths(string $path, ...$paths) : string
    {
        if (!str_ends_with($path, DIRECTORY_SEPARATOR)) {
            $path .= DIRECTORY_SEPARATOR;
        }
        foreach ($paths as $key => $p) {
            if (is_null($p)) {
                continue;
            }
            if (!str_ends_with($path, DIRECTORY_SEPARATOR)) {
                $path .= DIRECTORY_SEPARATOR;
            }
            if (str_starts_with($p, DIRECTORY_SEPARATOR)) {
                $path = $p;
            } else {
                $path .= $p;
            }
        }
        return $path;
    }

    /**
     * 基础目录
     */
    public function basePath(string $path = null) : string
    {
        return $this->paths($this->basePath, $path);
    }

    /**
     * 更改基础目录
     */
    public function useBasePath(string $path) : string
    {
        return $this->basePath = $path;
    }

    /**
     * 应用目录
     */
    public function appPath(string $path = null) : string
    {
        return $this->paths($this->basePath(), $this->appPath ?? 'app', $path);
    }

    /**
     * 更改应用目录
     */
    public function useAppPath(string $path) : static
    {
        $this->appPath = $path;

        return $this;
    }

    /**
     * 配置目录
     */
    public function configPath(string $path = null) : string
    {
        return $this->paths($this->basePath(), $this->configPath ?? 'config', $path);
    }

    /**
     * 更改配置目录
     */
    public function useConfigPath(string $path) : static
    {
        $this->configPath = $path;

        return $this;
    }

    /**
     * 路由目录
     */
    public function routePath(string $path = null) : string
    {
        return $this->paths($this->configPath(), $this->routePath ?? 'route', $path);
    }

    /**
     * 更改路由目录
     */
    public function useRoutePath(string $path) : static
    {
        $this->routePath = $path;

        return $this;
    }

    /**
     * 运行时目录
     */
    public function runtimePath(string $path = null) : string
    {
        return $this->paths($this->basePath(), $this->runtimePath ?? 'runtime', $path);
    }

    /**
     * 更改运行时目录
     */
    public function useRuntimePath(string $path) : static
    {
        $this->runtimePath = $path;

        return $this;
    }

    /**
     * 日志目录
     */
    public function logPath(string $path = null) : string
    {
        return $this->paths($this->runtimePath(), $this->logPath ?? 'log', $path);
    }

    /**
     * 更改日志目录
     */
    public function useLogPath(string $path) : static
    {
        return $this->logPath = $path;

        return $this;
    }

    /**
     * 缓存目录
     */
    public function cachePath(string $path = null) : string
    {
        return $this->paths($this->runtimePath(), $this->cachePath ?? 'cache', $path);
    }

    /**
     * 更改缓存目录
     */
    public function useCachePath(string $path) : static
    {
        $this->cachePath = $path;

        return $this;
    }
}