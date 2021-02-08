<?php
declare(strict_types=1);

namespace Minimal;

use ErrorException;
use RuntimeException;
use Swoole\Coroutine;
use Minimal\Facades\Facade;
use Minimal\Container\Container;
use Minimal\Annotation\Annotation;

/**
 * 应用类
 */
class Application
{
    /**
     * 容器对象
     */
    protected Container $container;

    /**
     * 路由器（含域名和路由列表）
     */
    protected array $router = [];

    /**
     * 事件集合
     */
    protected array $events = [];

    /**
     * 构造函数
     */
    public function __construct(protected string $basePath)
    {
        // 绑定错误
        set_error_handler(function($errno, $message, $file, $line){
            throw new ErrorException($message, 0, $errno, $file, $line);
        });
        // 捕获异常
        set_exception_handler(function($th){
            echo '[ ' . date('Y-m-d H:i:s') . ' ] ' . __CLASS__ . PHP_EOL;
            echo 'Messgae::' . $th->getMessage() . PHP_EOL;
            echo 'File::' . $th->getFile() . PHP_EOL;
            echo 'Line::' . $th->getLine() . PHP_EOL;
            echo PHP_EOL;
        });
        // 容器对象
        $this->container = new Container();
        $this->container->set(Container::class, $this->container);
        $this->container->set(Application::class, $this);
        // 配置对象
        $files = glob($basePath . '/config/*.php');
        $configs = [];
        foreach ($files as $key => $file) {
            $configs[pathinfo($file, PATHINFO_FILENAME)] = require $file;
        }
        $config = new Config($configs);
        $this->container->set('config', $config);
        // 注解处理
        $annotation = new Annotation($this->container);
        // 扫描：框架注解
        $annotation->scan(__DIR__, [
            'namespace' =>  __NAMESPACE__
        ]);
        // 扫描：应用注解
        $annotation->scan($basePath);
        // 门面注入
        Facade::setContainer($this->container);
    }

    /**
     * 添加路由
     */
    public function addRoute(string $path, array $methods = ['POST'], array $context = []) : int
    {
        // 全局路由器
        $this->router['routes'] = $this->router['routes'] ?? [];
        // 路由对象
        $route = array_merge($context, [
            'path'          =>  $path,
            'methods'       =>  $methods,
        ]);
        // 添加路由并得到索引编号
        $routeId = array_push($this->router['routes'], $route) - 1;
        // 循环域名，并根据路径保存到域名下
        foreach ($context['domains'] as $domain) {
            $this->router['domains'][$domain][$path] = $routeId;
        }
        // 返回索引
        return $routeId;
    }

    /**
     * 获取路由
     */
    public function getRoute(string $path, string $domain) : ?array
    {
        foreach (array_keys($this->router['domains'] ?? []) as $value) {
            if ($value == '*' || preg_match('/^' . str_replace('*', '[a-zA-Z0-9-]+', $value) . '$/', $domain)) {
                if (isset($this->router['domains'][$value][$path])) {
                    return $this->router['routes'][$this->router['domains'][$value][$path]];
                }
            }
        }
        return null;
    }

    /**
     * 获取路由器
     */
    public function getRouter() : ?array
    {
        return $this->router;
    }

    /**
     * 获取上下文
     */
    public function getContext() : array
    {
        return [
            'basePath'  =>  $this->basePath,
        ];
    }

    /**
     * 监听事件
     */
    public function on(string $name, callable $callback, int $priority = 0) : void
    {
        if (!isset($this->events[$name])) {
            $this->events[$name] = [];
        }
        $index = count($this->events[$name]);
        foreach ($this->events[$name] as $key => $array) {
            if ($priority > $array['priority']) {
                $index = $key;
                break;
            }
        }
        array_splice($this->events[$name], $index, 0, [[
            'callable'  =>  $callback,
            'priority'  =>  $priority,
        ]]);
    }

    /**
     * 触发事件
     */
    public function trigger(string $name, array $arguments = []) : bool
    {
        $events = $this->events[$name] ?? [];
        foreach ($events as $key => $array) {
            $bool = $this->container->call($array['callable'], $name, $arguments);
            if (false === $bool) {
                return false;
            }
        }
        return true;
    }

    /**
     * 执行命令
     */
    public function execute(array $argv = []) : void
    {
        // 脚本命令
        $argv = $argv ?: $_SERVER['argv'];
        $script = array_shift($argv);
        $command = array_shift($argv);
        if (is_null($command)) {
            die(sprintf('Tips: php %s start [-key value]' . PHP_EOL, $script));
        }
        // 获取参数
        $arguments = [];
        $lastKey = null;
        foreach ($argv as $v) {
            if (is_null($lastKey)) {
                $lastKey = trim($v, '-');
            } else if (0 === strpos($v, '-')) {
                $arguments[$lastKey] = null;
                $lastKey = trim($v, '-');
            } else {
                $arguments[$lastKey] = $v;
                $lastKey = null;
            }
        }
        if (!is_null($lastKey)) {
            $arguments[$lastKey] = null;
        }
        // 转向事件
        $this->__call($command, $arguments);
    }

    /**
     * 未知函数
     * 转向事件触发
     */
    public function __call(string $method, array $arguments)
    {
        // 拆解命令
        [$prefix, $method] = false !== strpos($method, ':')
            ? explode(':', $method, 2)
            : ['Application', $method];
        // 事件正名
        $prefix = ucfirst($prefix);
        $method = ucfirst($method);
        $event = $prefix . ':On' . $method;
        // 执行启动
        if (!in_array($prefix, ['Application', 'Server'])) {
            // 协程环境
            Coroutine\run(function() use($event, $arguments){
                // 数据库初始化
                if (!$this->trigger('Database:OnInit')) {
                    throw new RuntimeException(sprintf('Database init fail！'));
                }
                // 缓存初始化
                if (!$this->trigger('Cache:OnInit')) {
                    throw new RuntimeException(sprintf('Cache init fail！'));
                }
                // 按情况处理
                if (isset($this->events[$event])) {
                    $this->trigger($event, $arguments);
                } else {
                    throw new RuntimeException(sprintf('trigger undefined event %s', $event));
                }
            });
        } else {
            // 按情况处理
            if (isset($this->events[$event])) {
                $this->trigger($event, $arguments);
            } else {
                throw new RuntimeException(sprintf('trigger undefined event %s', $event));
            }
        }
    }
}