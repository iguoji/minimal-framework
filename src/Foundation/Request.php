<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;

/**
 * 请求类
 */
class Request
{
    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 设置句柄
     */
    public function setHandle(mixed $object) : static
    {
        $this->app->context->set(__CLASS__, $object);

        return $this;
    }

    /**
     * 获取句柄
     */
    public function getHandle() : mixed
    {
        return $this->app->context->get(__CLASS__);
    }

    /**
     * 获取全部数据
     */
    public function all() : array
    {
        return array_merge($this->getHandle()->get ?? [], $this->getHandle()->post ?? []);
    }

    /**
     * 获取输入数据
     */
    public function input(string $name, mixed $default = null) : mixed
    {
        return array_merge($this->getHandle()->get ?? [], $this->getHandle()->post ?? [])[$name] ?? $default;
    }

    /**
     * 获取查询字符串
     */
    public function query(string $name = null, mixed $default = null) : mixed
    {
        if (0 === func_num_args()) {
            return array_merge($this->getHandle()->get ?? [], $default ?? []);
        }

        return ($this->getHandle()->get ?? [])[$name] ?? $default;
    }

    /**
     * 获取Http头信息
     */
    public function header(string $name = null) : mixed
    {
        return isset($name) ? ($this->getHandle()->header[$name] ?? null) : ($this->getHandle()->header ?? []);
    }

    /**
     * 获取服务器信息
     */
    public function server(string $name = null) : mixed
    {
        return isset($name) ? ($this->getHandle()->server[$name] ?? null) : ($this->getHandle()->server ?? []);
    }

    /**
     * 获取会话信息
     */
    public function cookie(string $name = null) : mixed
    {
        return isset($name) ? ($this->getHandle()->cookie[$name] ?? null) : ($this->getHandle()->cookie ?? []);
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->getHandle()->$method(...$arguments);
    }
}