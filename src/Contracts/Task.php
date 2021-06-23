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
     */
    public function active() : bool;

    /**
     * 时间间隔
     * 单位毫秒，1000毫秒等于1秒
     */
    public function interval() : int;

    /**
     * 处理程序
     */
    public function handle() : bool;
}