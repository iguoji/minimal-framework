<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Support\Str;
use Minimal\Application;
use Minimal\Support\Traits\Config as ConfigTrait;

/**
 * Session会话类
 */
class Session
{
    /**
     * 配置函数
     */
    use ConfigTrait;

    /**
     * 全局配置
     */
    protected array $config = [
        'name'      =>  'session_id',
        'path'      =>  '/',
        'prefix'    =>  '',
        'length'    =>  64,
        'expire'    =>  60 * 60 * 24,
        'header'    =>  ['authorization', 'Session'],
    ];

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 合并配置
        $this->config = array_merge($this->config, $app->config->get('session', []));
    }




    /**
     * 启动新会话或者重用现有会话
     */
    public function start(Request $req) : bool
    {
        // 获取SessionId
        $sessionId = $req->cookie($this->name());
        if (empty($sessionId)) {
            // 从Header中获取
            $header = $req->header($this->config['header'][0]) ?? '';
            if (str_starts_with($header, $this->config['header'][1])) {
                $sessionId = substr($header, strlen($this->config['header'][1]) + 1);
            } else {
                $sessionId = $this->createId();
            }
        }

        // 设置SessionId
        $this->id($sessionId);

        // 返回结果
        return true;
    }

    /**
     * 读取/设置会话名称
     */
    public function name(string $name = null) : string
    {
        if (isset($name)) {
            $this->config['name'] = $name;
        }

        return $this->config['name'];
    }

    /**
     * 拼合Key
     */
    public function key(string|int ...$keys) : string
    {
        array_unshift($keys, $this->name());

        $keys = array_filter($keys);

        return implode(':', $keys);
    }

    /**
     * 获取/设置当前会话 ID
     * 如果当前没有会话，则返回空字符串（""）。
     */
    public function id(string $id = null, int $expire = null) : string
    {
        if (is_null($id)) {
            $id = $this->app->context->get($this->name());
        } else {
            $this->app->context->set($this->name(), $id);
        }

        return $id ?? '';
    }

    /**
     * 创建一个新的会话ID
     */
    public function createId(string $prefix = '') : string|bool
    {
        $id = ($prefix ?: $this->config['prefix']) . Str::random($this->config['length']);

        return $id;
    }

    /**
     * 返回当前会话状态
     */
    public function status(string $id = null) : int
    {
        if ((!is_null($id) && $this->app->cache->has($this->name() . ':' . $id)) || !empty($this->id())) {
            return PHP_SESSION_ACTIVE;
        }

        return PHP_SESSION_NONE;
    }

    /**
     * 到期时间
     */
    public function expire(string|int $key = '') : int
    {
        return $this->app->cache->ttl($this->key($this->id(), $key));
    }





    /**
     * 获取数据
     */
    public function get(string|int $key, mixed $default = null) : mixed
    {
        $data = $this->app->cache->get($this->key($this->id(), $key), $default);

        return $data === $default ? $default : unserialize($data);
    }

    /**
     * 获取全部数据
     */
    public function all() : array
    {
        $keys = $this->app->cache->keys($this->key($this->id()));
        $data = $this->app->cache->mGet($keys);
        $data = false === $data ? [] : $data;
        foreach ($data as $key => $value) {
            $data[$key] = unserialize($value);
        }
        return $data;
    }

    /**
     * 设置数据
     */
    public function set(string|int $key, mixed $value, int $expire = null) : mixed
    {
        // 过期时间
        $expire = $expire ?? $this->config['expire'];

        // 保存SessionId到缓存中
        if ($expire > $this->expire()) {
            $this->app->cache->set($this->key($this->id()), time(), $expire ?? $this->config['expire']);
        }

        return $this->app->cache->set($this->key($this->id(), $key), serialize($value), $expire);
    }

    /**
     * 是否存在数据
     */
    public function has(string|int $key) : bool
    {
        return $this->app->cache->has($this->key($this->id(), $key));
    }

    /**
     * 删除数据
     */
    public function delete(string|int $key) : void
    {
        $this->app->cache->delete($this->key($this->id(), $key));
    }

    /**
     * 删除所有数据
     */
    public function clear() : void
    {
        $keys = $this->app->cache->keys($this->key($this->id()) . '*');
        $this->app->cache->del($keys);
    }
}