<?php
declare(strict_types=1);

namespace App\Task;

use Minimal\Application;
use Minimal\Facades\Log;
use Minimal\Contracts\Task;

/**
 * 任务类
 */
class Hello implements Task
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * 是否激活
     */
    public function active() : bool
    {
        return false;
    }

    /**
     * 时间间隔
     */
    public function interval() : int
    {
        return 1000 * 10;
    }

    /**
     * 处理程序
     */
    public function handle() : bool
    {
        Log::debug('我是Hello任务类：' . time());

        return true;
    }

}