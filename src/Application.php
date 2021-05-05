<?php
declare(strict_types=1);

namespace Minimal;

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
    const VERSION = '0.1.0';

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
        'server'            =>  \Minimal\Foundation\Server::class,
        'route'             =>  \Minimal\Foundation\Route::class,
        'cache'             =>  \Minimal\Foundation\Cache::class,
        // 'database'          =>  \Minimal\Database\Manager::class,    // 在事件里加载
        'queue'             =>  \Minimal\Foundation\Queue::class,
        'view'              =>  \Minimal\Foundation\View::class,
        'context'           =>  \Minimal\Foundation\Context::class,
    ];

    /**
     * 事件集合
     */
    protected array $listeners = [
        \Minimal\Listeners\Application\OnStart::class,
        \Minimal\Listeners\Application\OnReload::class,
        \Minimal\Listeners\Application\OnRestart::class,
        \Minimal\Listeners\Application\OnStatus::class,
        \Minimal\Listeners\Application\OnStop::class,

        \Minimal\Listeners\Database\OnInit::class,
    ];

    /**
     * 构造函数
     */
    public function __construct(string $basePath)
    {
        // 绑定容器
        $this->set('app', $this);
        $this->set('container', $this);
        // 门面注入
        Facade::setContainer($this);
        // 基础目录
        $this->useBasePath($basePath);
        // 错误绑定
        $this->bindErrorHandler();
        // 事件绑定
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
            $logger = null;
            if ($this->app->has('log')) {
                $logger = $this->app->get('log');
            }
            if (is_null($logger)) {
                echo '[ ' . date('Y-m-d H:i:s') . ' ] ' . __CLASS__ . PHP_EOL;
                echo 'Messgae::' . $th->getMessage() . PHP_EOL;
                echo 'File::' . $th->getFile() . PHP_EOL;
                echo 'Line::' . $th->getLine() . PHP_EOL;
                echo PHP_EOL;
            } else {
                $logger->error($th->getMessage(), [
                    'File'   =>  $th->getFile(),
                    'Line'   =>  $th->getLine(),
                ]);
            }
        });
    }

    /**
     * 事件绑定
     */
    public function bindListeners() : void
    {
        $userEvents = $this->config->get('listener', []);
        $listeners = array_merge($this->listeners, $userEvents);
        foreach ($listeners as $class) {
            $this->event->bind($class);
        }
    }

    /**
     * 执行命令
     */
    public function execute(array $arguments = []) : void
    {
        if (count($arguments) < 2) {
            return;
        }
        $script = array_shift($arguments);

        $array = explode(':', array_shift($arguments), 2);
        if (1 === count($array)) {
            array_unshift($array, 'Application');
        }
        $array = array_map(fn($s) => ucwords($s), $array);
        if (!str_starts_with($array[1], 'On')) {
            $array[1] = 'On' . $array[1];
        }
        $event = implode(':', $array);

        $parameters = [];
        foreach ($arguments as $value) {
            $array = explode('=', $value, 2);
            if (1 === count($array)) {
                $array[] = true;
            }
            $parameters[ltrim($array[0], '-')] = $array[1];
        }

        $this->event->trigger($event, $parameters);
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