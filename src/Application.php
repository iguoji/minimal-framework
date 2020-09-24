<?php
declare(strict_types=1);

namespace Minimal;

use Attribute;
use ReflectionClass;
use ReflectionMethod;
use InvalidArgumentException;
use UnexpectedValueException;
use Minimal\Contracts\Annotation;

/**
 * 应用类
 */
class Application extends Container
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
        $this->container = new Container();
        $this->container->set(Container::class, $this->container);
        $this->container->set(Application::class, $this);

        $this->annotation($basePath);
    }

    /**
     * 注解解析
     */
    public function annotation(string $path) : void
    {
        // 循环扫描文件夹
        if (is_dir($path)) {
            $paths = glob($path . DIRECTORY_SEPARATOR . '*');
            foreach ($paths as $path) {
                $this->annotation($path);
            }
        } else {
            // 根据路径得到类名
            $class = mb_substr($path, mb_strlen($this->basePath), -4);
            $class = trim($class, DIRECTORY_SEPARATOR);
            $class = trim(mb_ereg_replace(DIRECTORY_SEPARATOR, '\\', $class));
            $class = ucwords($class);
            // 上下文
            $context = ['path' => $path, 'class' => $class];
            // 解析注解并填充上下文和返回待处理注解实例
            $parse = function(array $attrs, array $context, array $queue = []) : array {
                // 循环所有注解
                foreach ($attrs as $attr) {
                    // 名字和标签
                    $name = $attr->getName();
                    $tag = mb_strtolower(mb_substr($name, mb_strrpos($name, '\\') + 1));
                    // 如果注解类不存在，当作全局属性
                    if (! class_exists($attr->getName())) {
                        $context[$tag] = $attr->getArguments();
                        continue;
                    }
                    // 实例化注解类
                    $ins = $this->container->make($attr->getName(), ...$attr->getArguments());
                    // 如果没有实现框架的注解接口，也当作全局属性
                    if (! $ins instanceof Annotation) {
                        $context[$tag] = $attr->getArguments();
                        continue;
                    }
                    // 保存到列队
                    $append = true;
                    foreach ($queue as $key => $item) {
                        if ($item::class == $ins::class) {
                            $append = false;
                            $queue[$key] = $ins;
                            break;
                        } else if ($ins->getPriority() > $item->getPriority()) {
                            $append = false;
                            array_splice($queue, $key, 0, [$ins]);
                            break;
                        }
                    }
                    if ($append) {
                        array_push($queue, $ins);
                    }
                }
                // 返回结果
                return [$context, $queue];
            };
            // 开始解析注解类
            if ($class && class_exists($class)) {
                // 上下文
                $context['target'] = Attribute::TARGET_CLASS;
                $context['instance'] = $this->container->make($class);
                // 解析类
                $refClass = new ReflectionClass($class);
                [$context, $queue] = $parse($refClass->getAttributes(), $context);
                // 解析所有方法
                foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
                    // 上下文
                    $context['target'] = Attribute::TARGET_METHOD;
                    $context['method'] = $refMethod->getName();
                    // 解析当前方法的注解
                    [$methodContext, $methodQueue] = $parse($refMethod->getAttributes(), $context, $queue);
                    array_walk($methodQueue, function($ins) use(&$methodContext){
                        $methodContext[$ins::class] = $ins->handle($methodContext);
                    });
                }
            }
        }
    }

    /**
     * 添加路由
     */
    public function addRoute(string $path, array $methods = ['POST'], array $callable, array $domains = ['*']) : int
    {
        // 全局路由器
        $router = &$this->router;
        $router['routes'] = $router['routes'] ?? [];
        // 将路由添加到路由器并得到索引编号
        $routeId = array_push($router['routes'], [
            'path'      =>  $path,
            'callable'  =>  $callable,
            'methods'   =>  $methods,
            'domains'   =>  $domains
        ]) - 1;
        // 循环域名，并根据路径保存到域名下
        foreach ($domains as $domain) {
            $router['domains'][$domain][$path] = $routeId;
        }
        // 返回索引
        return $routeId;
    }

    /**
     * 监听事件
     */
    public function on(string $event, string $listener)
    {
        printf('Event: %s, For: %s', $event, $listener);
        echo PHP_EOL;
    }

    /**
     * 触发事件
     */
    public function trigger(string $name, ...$arguments)
    {
        // printf("[%s] \t %s", $name, implode(', ', $arguments));
        print_r($name);
        echo PHP_EOL;
        // print_r($arguments);
        // echo PHP_EOL;
    }

    /**
     * 启动应用
     */
    public function start(array $settings = [])
    {
        $server = new \Swoole\Http\Server('0.0.0.0', 80);
        $server->set(array_merge([
            'pid_file'      =>  $this->basePath . '/pid',
            'worker_num'    =>  swoole_cpu_num(),
            'reload_async'  =>  true,
            'max_wait_time' =>  60,
        ], $settings));
        foreach([
            'Start'             =>  'Server:OnStart',
            'Shutdown'          =>  'Server:OnShutdown',
            'WorkerStart'       =>  'Server:OnWorkerStart',
            'WorkerStop'        =>  'Server:OnWorkerStop',
            'WorkerExit'        =>  'Server:OnWorkerExit',
            'Request'           =>  'Server:OnRequest',
            'Connect'           =>  'Server:OnConnect',
            // 'Receive'           =>  'Server:OnReceive',
            // 'Packet'            =>  'Server:OnPacket',
            'Close'             =>  'Server:OnClose',
            'HandShake'         =>  'Server:OnHandShake',
            'Open'              =>  'Server:OnOpen',
            'Message'           =>  'Server:OnMessage',
            'Task'              =>  'Server:OnTask',
            'Finish'            =>  'Server:OnFinish',
            'PipeMessage'       =>  'Server:OnPipeMessage',
            'WorkerError'       =>  'Server:OnWorkerError',
            'ManagerStart'      =>  'Server:OnManagerStart',
            'ManagerStop'       =>  'Server:OnManagerStop',
            'BeforeReload'      =>  'Server:OnBeforeReload',
            'AfterReload'       =>  'Server:OnAfterReload',
        ] as $swooleEvent => $minimalEvent) {
            $server->on($swooleEvent, function(...$arguments) use($minimalEvent) {
                $this->trigger($minimalEvent, ...$arguments);
            });
        };
        $server->start();
    }
}