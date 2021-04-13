<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use RuntimeException;
use Minimal\Application;

/**
 * 服务类
 */
class Service
{
    /**
     * 服务集合
     */
    protected array $services = [];
    protected array $registeredServices = [];

    /**
     * 构造方法
     */
    public function __construct(protected Application $app, array $userServices = [])
    {
        $this->services = $userServices;
    }

    /**
     * 注册服务
     */
    public function register(string $alias, string $class = null, bool $force = false) : void
    {
        $class = $class ?? $this->services[$alias] ?? null;
        if (is_null($class)) {
            throw new RuntimeException("service [$alias] could not register.");
        }

        if (!isset($this->services[$alias]) || $this->services[$alias] != $class) {
            $this->services[$alias] = $class;
            $force = true;
        }

        if (false === $force && isset($this->registeredServices[$alias])) {
            return;
        }

        $serviceIns = $this->app->make($class);
        $serviceIns->register();

        $this->registeredServices[$alias] = $serviceIns;
    }

    /**
     * 注册所有服务
     */
    public function registerServices() : void
    {
        array_walk($this->services, fn($class, $as) => $this->register($as, $class));
    }

    /**
     * 启动所有服务
     */
    public function bootServices() : void
    {
        array_walk($this->registeredServices, fn($ins) => $ins->boot());
    }
}