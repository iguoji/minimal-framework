<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 事件监听类
 */
interface Listener
{
    /**
     * 获取监听的事件及优先级
     */
    public function events() : array;

    /**
     * 处理程序
     */
    public function handle(string $event, array $arguments = []) : bool;
}