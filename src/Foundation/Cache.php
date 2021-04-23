<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Redis;
use Throwable;
use Minimal\Application;

/**
 * 缓存类
 */
class Cache
{
    /**
     * 驱动句柄
     */
    protected Redis $handle;

    /**
     * 配置信息
     */
    protected array $config;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 获取配置
        $config = $app->config->get('cache', []);
        // 保存配置
        $this->config = array_merge($this->getDefaultConfigStruct(), $config);
        // 创建连接
        $this->connect();
    }

    /**
     * 获取默认配置结构
     */
    public function getDefaultConfigStruct() : array
    {
        return [
            'host'          =>  '127.0.0.1',
            'port'          =>  6379,
            'expire'        =>  0,
            'select'        =>  0,
            'auth'          =>  [],
            'timeout'       =>  2,
            'options'       =>  [],
        ];
    }

    /**
     * 创建连接
     */
    public function connect(int $reconnect = 1) : mixed
    {
        try {
            $this->handle = new Redis();
            $this->handle->connect($this->config['host'], $this->config['port'],  $this->config['timeout']);
            if (!empty($this->config['auth'])) {
                $this->handle->auth($this->config['auth']);
            }
            $this->handle->select($this->config['select']);
            foreach ($this->config['options'] as $key => $value) {
                $this->handle->setOption($key, $value);
            }
            return $this->handle;
        } catch (Throwable $th) {
            if ($reconnect > 0) {
                return $this->connect($reconnect - 1);
            }
            throw $th;
        }
    }

    /**
     * 释放连接
     */
    public function release() : void
    {
    }

    /**
     * 是否存在
     */
    public function has(string|int $key) : bool
    {
        return (bool) $this->__call('exists', [$key]);
    }

    /**
     * 获取数据
     */
    public function get(string|int $key, mixed $default = null) : mixed
    {
        $value = $this->__call('get', [$key]);
        return false === $value ? $default : $value;
    }

    /**
     * 设置数据
     */
    public function set(string|int $key, mixed $value, int $expire = null) : bool
    {
        $expire = $expire ?? $this->config['expire'] ?? null;
        if (is_null($expire) || $expire === 0) {
            return true === $this->__call('set', [$key, $value]);
        } else {
            return true === $this->__call('setEx', [$key, $expire, $value]);
        }
    }

    /**
     * 自增数据
     */
    public function inc(int|string $key, int $step = 1) : int|bool
    {
        return $this->__call('incrby', [$key, $step]);
    }

    /**
     * 自减数据
     */
    public function dec(int|string $key, int $step = 1) : int|bool
    {
        return $this->__call('decrby', [$key, $step]);
    }

    /**
     * 删除数据
     */
    public function delete(string|int $key) : bool
    {
        return (bool) $this->__call('del', [$key]);
    }

    /**
     * 清空数据
     */
    public function clear() : bool
    {
        $this->__call('flushDB', []);
        return true;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->handle->$method(...$arguments);
    }
}