<?php
declare(strict_types=1);

namespace Minimal\Foundation;

/**
 * 请求类
 */
class Request
{
    /**
     * 原始请求
     */
    protected mixed $handle;

    /**
     * 设置句柄
     */
    public function handle(mixed $object) : static
    {
        $this->handle = $object;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->handle->$method(...$arguments);
    }
}