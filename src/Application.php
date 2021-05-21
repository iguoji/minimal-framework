<?php
declare(strict_types=1);

namespace Minimal;

use Minimal\Support\Path;
use Minimal\Foundation\Facade;
use Minimal\Foundation\Container;

/**
 * 应用类
 */
class Application extends Container
{
    /**
     * 版本号码
     */
    const VERSION = '0.1.1';

    /**
     * 目录列表
     */
    protected string $basePath;         // 基础目录：  /
    protected string $appPath;          // 应用目录：  /app/
    protected string $viewPath;         // 模板目录：  /app/View/
    protected string $configPath;       // 配置目录：  /config/
    protected string $routePath;        // 路由目录：  /config/route/
    protected string $runtimePath;      // 运行时目录：/runtime/
    protected string $logPath;          // 日志目录：  /runtime/logs/
    protected string $cachePath;        // 缓存目录：  /runtime/cache/

    /**
     * 别名集合
     */
    protected array $aliases = [
        'env'               =>  \Minimal\Foundation\Env::class,
        'config'            =>  \Minimal\Foundation\Config::class,
        'event'             =>  \Minimal\Foundation\Event::class,
        'log'               =>  \Minimal\Foundation\Log::class,
        'server'            =>  \Minimal\Server\Manager::class,
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
        // 门面注入
        Facade::setContainer($this);

        // 错误绑定
        $this->bindErrorHandler();
        // 监听器绑定
        $this->bindListeners();
    }

    /**
     * 错误绑定
     */
    public function bindErrorHandler() : void
    {
        // 错误程序
        set_error_handler(function($errno, $message, $file, $line){
            throw new \ErrorException($message, 0, $errno, $file, $line);
        });
        // 异常程序
        set_exception_handler(function($th){
            $this->app->log->error($th->getMessage(), [
                'File'   =>  $th->getFile(),
                'Line'   =>  $th->getLine(),
            ]);
        });
    }

    /**
     * 监听器绑定
     */
    public function bindListeners() : void
    {
        $listeners = $this->config->get('listener', []);
        foreach ($listeners as $class) {
            $this->event->bind($class);
        }
    }

    /**
     * 执行命令
     */
    public function execute(array $arguments = []) : void
    {
        if (count($arguments) < 3) {
            return;
        }

        $script = array_shift($arguments);
        $class = array_shift($arguments);
        $method = array_shift($arguments);

        var_dump(
            $this->get($class)->$method(...$arguments)
        );
    }

    /**
     * 目录拼接
     */
    public function paths(?string ...$paths) : string
    {
        return DIRECTORY_SEPARATOR . Path::absolute(implode(DIRECTORY_SEPARATOR, $paths));
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
     * 模板目录
     */
    public function viewPath(string $path = null) : string
    {
        return $this->paths($this->appPath(), $this->viewPath ?? 'View', $path);
    }

    /**
     * 更改模板目录
     */
    public function useViewPath(string $path) : static
    {
        $this->viewPath = $path;

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