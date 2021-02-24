<?php
declare(strict_types=1);

namespace Minimal\Listeners\Server;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 当 Worker/Task 进程发生异常后会在 Manager 进程内回调此函数。
 */
#[Listener]
class OnWorkerError implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct()
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Server:OnWorkerError'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 错误信息
        if ($error = error_get_last()) {
            var_dump($error);
        }

        /**
         * array(4) {
            [0]=>
            int(0)
            [1]=>
            int(12500)
            [2]=>
            int(255)
            [3]=>
            int(0)
           }
         */
        // array_shift($arguments);
        // var_dump($arguments);
        // 打印信息
        // $this->log->error(__CLASS__ . '::' . $event, $error ?? []);
        // 继续执行
        return true;
    }
}