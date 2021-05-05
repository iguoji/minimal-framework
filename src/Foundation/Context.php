<?php
declare(strict_types=1);

namespace Minimal\Foundation;

/**
 * 上下文类
 */
class Context
{
    /**
     * 设置数据
     */
    public function set(string $key, mixed $value) : void
    {
        \Swoole\Coroutine::getContext()[$key] = $value;
    }

    /**
     * 获取数据
     */
    public function get(string $key, mixed $default = null) : mixed
    {
        return \Swoole\Coroutine::getContext()[$key] ?? $default;
    }

    /**
     * 是否存在数据
     */
    public function has(string $key) : bool
    {
        return isset(\Swoole\Coroutine::getContext()[$key]);
    }

    /**
     * 删除数据
     */
    public function del(string $key) : void
    {
        unset(\Swoole\Coroutine::getContext()[$key]);
    }

    /**
     * 数据自增
     */
    public function inc(string $key, int|float $step = 1) : int|float
    {
        $value = $this->get($key, 0);
        $value += $step;
        $this->set($key, $value);

        return $value;
    }

    /**
     * 数据自减
     */
    public function dec(string $key, int|float $step = 1) : int|float
    {
        $value = $this->get($key, 0);
        $value -= $step;
        $this->set($key, $value);

        return $value;
    }
}