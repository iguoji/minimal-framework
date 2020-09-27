<?php
declare(strict_types=1);

namespace Minimal;

use RuntimeException;

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
     * 路由器
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
        // 容器对象
        $this->container = new Container();
        $this->container->set(Container::class, $this->container);
        $this->container->set(Application::class, $this);
        // 注解处理
        $annotation = new Annotation($this->container);
        $annotation->scan(__DIR__, [
            'namespace' =>  __NAMESPACE__
        ]);
        $annotation->scan($basePath);
    }

    /**
     * 添加路由
     */
    public function addRoute(string $path, array $methods = ['POST'], array $context) : int
    {
        // 全局路由器
        $router = &$this->router;
        $router['routes'] = $router['routes'] ?? [];
        // 将路由添加到路由器并得到索引编号
        $routeId = array_push($router['routes'], [
            'path'          =>  $path,
            'callable'      =>  [$context['instance'], $context['method']],
            'methods'       =>  $methods,
            'domains'       =>  $context['domains'] ?? ['*'],
            'middlewares'   =>  $context['middlewares'] ?? [],
            'validate'      =>  $context['validate'],
        ]) - 1;
        // 循环域名，并根据路径保存到域名下
        foreach ($context['domains'] ?? ['*'] as $domain) {
            $router['domains'][$domain][$path] = $routeId;
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
     * 监听事件
     */
    public function on(string $eventName, callable $callback, int $priority = 0) : void
    {
        if (!isset($this->events[$eventName])) {
            $this->events[$eventName] = [];
        }
        $index = count($this->events[$eventName]);
        foreach ($this->events[$eventName] as $key => $array) {
            if ($priority > $array['priority']) {
                $index = $key;
                break;
            }
        }
        array_splice($this->events[$eventName], $index, 0, [[
            'callable'  =>  $callback,
            'priority'  =>  $priority,
        ]]);
    }

    /**
     * 触发事件
     */
    public function trigger(string $eventName, ...$arguments) : void
    {
        foreach ($this->events[$eventName] ?? [] as $key => $array) {
            $result = $this->container->call($array['callable'], $eventName, ...$arguments);
            if ($result === false) {
                break;
            }
        }
    }

    /**
     * 未知函数
     * 转向事件触发
     */
    public function __call(string $method, array $arguments)
    {
        $eventName = 'Application:On' . ucfirst($method);
        if (isset($this->events[$eventName])) {
            $this->trigger($eventName, ...$arguments);
        } else {
            throw new RuntimeException(sprintf('call to undefined method Minimal\Application::%s()', $method));
        }
    }
}