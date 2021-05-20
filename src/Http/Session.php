<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Application;
use Minimal\Foundation\Cache;
use Minimal\Foundation\Config;
use Minimal\Support\Str;

/**
 * 会话类(Session)
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
     * 构造方法
     */
    public function __construct(protected Application $app)
    {
        // 获取配置
        $this->config = array_merge($this->config, $app->config->get('session', []));
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
    public function start(array $cookie = []) : void
    {
        $name = $this->config['name'];
        $token = $cookie[$name] ?? $this->createSessionId();

        $this->app->context->set('session:token',  $token);
        $this->app->cookie->set($name, $token);
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
        $this->app->cache->set($this->parseKey($key), $value, $expire);
    }

    /**
     * 获取数据
     */
    public function get(string|int $key, mixed $default = null) : mixed
    {
        return $this->app->cache->get($this->parseKey($key), $default);
    }

    /**
     * 获取所有
     */
    public function all() : array
    {
        $keys = $this->app->cache->keys($this->parseKey() . '*');

        return $keys ? $this->app->cache->mGet($key) : [];
    }

    /**
     * 是否存在数据
     */
    public function has(string|int $key) : bool
    {
        return $this->app->cache->has($this->parseKey($key));
    }

    /**
     * 删除一个数据
     */
    public function delete(string|int $key) : void
    {
        $this->app->cache->delete($this->parseKey($key));
    }

    /**
     * 删除所有数据
     */
    public function clear() : void
    {
        $keys = $this->app->cache->keys($this->parseKey() . '*');

        $this->app->cache->del($keys);
    }

    /**
     * 解析Key
     */
    public function parseKey(string|int $key = null) : string
    {
        $result = 'session';
        $token = $this->getSessionId();
        if (!empty($token)) {
            $result .= ':' . $token;
        }
        return $result . (isset($key) ? ':' . $key : $key);
    }
}