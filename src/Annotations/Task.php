<?php
declare(strict_types=1);

namespace Minimal\Annotations;

use Attribute;
use UnexpectedValueException;
use Swoole\Timer;
use Swoole\Coroutine;
use Minimal\Application;
use Minimal\Container\Container;
use Minimal\Annotation\AnnotationInterface;
use Minimal\Contracts\Task as TaskInterface;

/**
 * 绑定任务
 */
#[Attribute]
class Task implements AnnotationInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container, protected Application $app)
    {}

    /**
     * 获取在上下文中的Key
     */
    public function getContextKey() : ?string
    {
        return null;
    }

    /**
     * 获取目标
     */
    public function getTargets() : array
    {
        return [Attribute::TARGET_CLASS];
    }

    /**
     * 获取优先级
     */
    public function getPriority() : int
    {
        return -10;
    }

    /**
     * 功能处理
     */
    public function handle(array $context) : mixed
    {
        // 实例判断
        $task = $this->container->make($context['class']);
        if (!$task instanceof TaskInterface) {
            throw new UnexpectedValueException(sprintf('Task "%s" must implements "%s"', $context['class'], TaskInterface::class));
        }
        // 绑定事件
        $this->app->on('Application:OnLaunch', function(string $event, array $arguments = []) use($task){
            echo PHP_EOL;
            echo __METHOD__, PHP_EOL;
            var_dump(count($arguments));
            foreach ($arguments as $arg) {
                echo gettype($arg), PHP_EOL;
            }
            echo PHP_EOL;
            /*// 协程处理
            Coroutine\run(function() use($task){
                // 有效任务
                if ($task->active() && $task->interval() > 0) {
                    // 多次任务
                    Timer::tick($task->interval(), function(int $timer_id) use($task){
                        // 删除任务
                        if (!$task->active() && $task->interval() <= 0) {
                            Timer::clear($timer_id);
                        } else if ($task->active()) {
                            // 激活了才执行任务
                            $task->handle();
                        }
                    });
                }
            });*/
        });
        return null;
    }
}