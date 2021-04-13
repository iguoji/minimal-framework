<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 事件监听接口
 */
interface Listener
{
    /**
     * 获取监听的事件及优先级
     */
    public function events() : array;

    /**
     * 处理程序
     * 返回false则后续相同事件不在继续处理
     */
    public function handle(string $event, array $arguments = []) : bool;
}