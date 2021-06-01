<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 服务器接口
 */
interface Server
{
    /**
     * 设置服务器配置
     */
    public function setConfig(string|int $key, mixed $value) : static;

    /**
     * 获取服务器配置
     */
    public function getConfig(string|int $key, mixed $default = null) : mixed;

    /**
     * 获取服务器全部配置
     */
    public function getConfigs() : array;

    /**
     * 获取服务器默认配置
     */
    public function getDefaultConfig() : array;

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