<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;
use Minimal\Support\Str;

/**
 * 会话类
 */
class Session
{
    /**
     * 系统配置
     */
    protected array $config = [
        'name'      =>  'session_id',
        'expire'    =>  60 * 60 * 24,
        'path'      =>  '/',
        'domain'    =>  '',
        'secure'    =>  false,
        'httponly'  =>  false,
        'samesite'  =>  '',
        'priority'  =>  '',
    ];

    /**
     * 存储对象
     */
    protected mixed $storage;

    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {
        // 获取配置
        $this->config = array_merge($this->config, $app->config->get('session', []));
        // 存储对象
        $this->storage = $app->cache;
    }

    /**
     * 获取配置
     */
    public function getConfig(string $key = null) : mixed
    {
        return isset($key) ? ($this->config[$key] ?? null) : ($this->config ?? []);
    }

    /**
     * 设置配置
     */
    public function setConfig(string $key, mixed $value) : void
    {
        $this->config[$key] = $value;
    }

    /**
     * 开启会话
     */
    public function start(string $token = null) : void
    {
        $this->app->context->set('session:token', $token ?? $this->createSessionId());
    }

    /**
     * 获取会话ID
     */
    public function getSessionId() : ?string
    {
        return $this->app->context->get('session:token');
    }

    /**
     * 创建会话ID
     */
    public function createSessionId() : string
    {
        return Str::random(32);
    }

    /**
     * 设置数据
     */
    public function set(string|int $key, mixed $value, int $expire = null) : void
    {
        $this->storage->set($this->parseKey($key), $value, $expire);
    }

    /**
     * 获取数据
     */
    public function get(string|int $key, mixed $default = null) : mixed
    {
        return $this->storage->get($this->parseKey($key), $default);
    }

    /**
     * 获取所有
     */
    public function all() : array
    {
        $keys = $this->storage->keys($this->parseKey('*'));

        return $keys ? $this->storage->mGet($key) : [];
    }

    /**
     * 是否存在数据
     */
    public function has(string|int $key) : bool
    {
        return $this->storage->has($this->parseKey($key));
    }

    /**
     * 删除一个数据
     */
    public function delete(string|int $key) : void
    {
        $this->storage->delete($this->parseKey($key));
    }

    /**
     * 删除所有数据
     */
    public function flush() : void
    {
        $keys = $this->storage->keys($this->parseKey('*'));

        $this->storage->del($keys);
    }

    /**
     * 解析Key
     */
    public function parseKey(string|int $key) : string
    {
        $token = $this->getSessionId();
        if (empty($token)) {
            return '';
        }
        return $token . '' . $key;
    }
}