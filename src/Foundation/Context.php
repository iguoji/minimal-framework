<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Swoole\Coroutine;

/**
 * 上下文类
 */
class Context
{
    /**
     * 数据源
     */
    protected array $dataset = [];

    /**
     * 获取编号
     */
    public function id(int|bool $cid = null) : int
    {
        // 当前编号
        $cid = $cid ?? Coroutine::getCid();
        if (-1 === $cid || false === $cid) {
            throw new \Swoole\Error('API must be called in the coroutine');
        }
        // 父类编号
        $pcid = Coroutine::getPcid($cid);
        if (-1 === $pcid || false === $pcid) {
            return $cid;
        }
        // 继续寻找
        return $this->id($pcid);
    }

    /**
     * 设置数据
     */
    public function set(string $key, mixed $value) : void
    {
        $this->dataset[$this->id()][$key] = $value;
    }

    /**
     * 获取数据
     */
    public function get(string $key, mixed $default = null) : mixed
    {
        return $this->dataset[$this->id()][$key] ?? $default;
    }

    /**
     * 是否存在数据
     */
    public function has(string $key) : bool
    {
        return isset($this->dataset[$this->id()][$key]);
    }

    /**
     * 删除数据
     */
    public function del(string $key) : void
    {
        unset($this->dataset[$this->id()][$key]);
    }

    /**
     * 清空资源
     */
    public function flush() : void
    {
        unset($this->dataset[$this->id()]);
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