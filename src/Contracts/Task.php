<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 任务接口
 */
interface Task
{
    /**
     * 是否激活
     * 与间隔配合可删除任务
     */
    public function active() : bool;

    /**
     * 时间间隔
     * 与激活配合可删除任务
     */
    public function interval() : int;

    /**
     * 处理程序
     */
    public function handle() : bool;
}