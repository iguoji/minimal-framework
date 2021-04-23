<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;
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
    public function __construct(protected Application $app)
    {
        $this->load();
    }

    /**
     * 载入配置
     */
    public function load() : void
    {
        $files = glob($this->app->configPath('*.php'));
        foreach ($files as $file) {
            $this->dataset[pathinfo($file, PATHINFO_FILENAME)] = require_once $file;
        }
    }

    /**
     * 获取所有配置
     */
    public function all() : array
    {
        return $this->dataset;
    }

    /**
     * 获取配置
     */
    public function get(string $key, mixed $default = null) : mixed
    {
        return Arr::get($this->dataset, $key, $default);
    }

    /**
     * 是否存在配置
     */
    public function has(string $key) : bool
    {
        return Arr::has($this->dataset, $key);
    }

    /**
     * 设置数据
     */
    public function set(string $key, mixed $value) : mixed
    {
        return Arr::set($this->dataset, $key, $value);
    }
}