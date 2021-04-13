<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Support\Arr;

/**
 * 配置类
 */
class Config
{
    /**
     * 数据源
     */
    protected array $dataset = [];

    /**
     * 构造函数
     */
    public function __construct(array $data)
    {
        $this->dataset = $data;
    }

    /**
     * 获取所有配置
     */
    public function all()
    {
        return $this->dataset;
    }

    /**
     * 获取配置
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->dataset, $key, $default);
    }

    /**
     * 是否存在配置
     */
    public function has($key)
    {
        return Arr::has($this->dataset, $key);
    }

    /**
     * 设置数据
     */
    public function set($key, $value)
    {
        return Arr::set($this->dataset, $key, $value);
    }
}