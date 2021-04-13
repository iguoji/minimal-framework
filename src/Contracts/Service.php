<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 服务抽象类
 */
interface Service
{
    /**
     * 服务注册
     * 主绑定容器，最好不要调用其他服务
     */
    public function register() : void;

    /**
     * 服务启动
     * 所有服务注册完成后依次启动
     */
    public function boot() : void;
}