<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Application;
use Minimal\Foundation\Config;

/**
 * 会话类(Cookie)
 */
class Cookie
{
    /**
     * 系统配置
     */
    protected array $config = [
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
        $this->config = array_merge($this->config, $app->config->get('cookie', []));
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
        $config = $this->getConfig();
        foreach ($cookie as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * 设置数据
     */
    public function set(string $key, string $value = '', int $expire = null , string $path = null, string $domain  = null, bool $secure = null , bool $httponly = null, string $samesite = null, string $priority = null) : void
    {
        $config = $this->getConfig();

        $this->app->context->set($this->parseKey($key), [
            'key'       =>  $key,
            'value'     =>  $value,
            'expire'    =>  time() + ($expire ?? $config['expire']),
            'path'      =>  $path ?? $config['path'],
            'domain'    =>  $domain ?? $config['domain'],
            'secure'    =>  $secure ?? $config['secure'],
            'httponly'  =>  $httponly ?? $config['httponly'],
            'samesite'  =>  $samesite ?? $config['samesite'],
            'priority'  =>  $priority ?? $config['priority'],
        ]);
    }

    /**
     * 获取数据
     */
    public function get(string|int $key, mixed $default = null) : mixed
    {
        return $this->context->get($this->parseKey($key . '.value'), $default);
    }

    /**
     * 获取所有
     */
    public function all() : array
    {
        return $this->app->context->get($this->parseKey());
    }

    /**
     * 是否存在数据
     */
    public function has(string|int $key) : bool
    {
        return $this->app->context->has($this->parseKey($key));
    }

    /**
     * 删除一个数据
     */
    public function delete(string|int $key) : void
    {
        $this->app->context->delete($this->parseKey($key));
    }

    /**
     * 删除所有数据
     */
    public function clear() : void
    {
        $this->app->context->delete($this->parseKey());
    }

    /**
     * 解析Key
     */
    public function parseKey(string|int $key = null) : string
    {
        return 'cookie' . (isset($key) ? '.' . $key : '');
    }
}