<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 服务器接口
 */
interface Server
{
    /**
     * 服务器配置
     */
    public function setConfig(array $config) : static;

    /**
     * 获取服务器配置
     */
    public function getConfig() : array;

    /**
     * 切换服务器
     */
    public function use(string $name) : static;

    /**
     * 启动服务器
     */
    public function start() : bool;

    /**
     * 停止服务器
     */
    public function stop() : bool;

    /**
     * 重载服务器
     */
    public function reload() : bool;

    /**
     * 重启服务器
     */
    public function restart() : bool;

    /**
     * 服务状态器
     */
    public function status() : bool;
}