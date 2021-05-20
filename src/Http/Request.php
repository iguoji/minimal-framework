<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Application;

/**
 * 请求类
 */
class Request
{
    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 设置句柄
     */
    public function setHandle(mixed $object) : static
    {
        $this->getContext()->set(__CLASS__, $object);

        // Cookie
        $this->app->cookie->start($object->cookie ?? []);
        // Session
        $this->app->session->start($object->cookie ?? []);

        return $this;
    }

    /**
     * 获取句柄
     */
    public function getHandle() : mixed
    {
        return $this->getContext()->get(__CLASS__);
    }

    /**
     * 获取上下文
     */
    public function getContext() : Context
    {
        return $this->app->context;
    }






    /**
     * 获取协议
     */
    public function scheme() : string
    {
        return !empty($this->server('https')) && $this->server('https') !== 'off' ? 'https' : 'http';
    }

    /**
     * 获取主机或域名
     * www.example.com
     * 192.168.2.12:8080
     */
    public function host() : string
    {
        return $this->header('host') ?? '';
    }

    /**
     * 获取请求路径
     * /
     * /boo/foo
     */
    public function path() : string
    {
        return $this->server('request_uri') ?? $this->server('path_info') ?? '';
    }

    /**
     * 获取查询字符串
     * a=1&b=2
     */
    public function queryString(bool $mark = false) : string
    {
        $qs = $this->server('query_string');
        return $qs ? ($mark ? '?' : '') . $qs : '';
    }

    /**
     * 获取完整URL
     */
    public function url() : string
    {
        return $this->scheme() . '://' . $this->host() . $this->path() . $this->queryString(true);
    }

    /**
     * 获取请求方式
     */
    public function method() : string
    {
        return $this->server('request_method');
    }

    /**
     * 判断请求方式
     */
    public function isGet() : bool
    {
        return $this->method() === 'GET';
    }

    /**
     * 判断请求方式
     */
    public function isPost() : bool
    {
        return $this->method() === 'POST';
    }





    /**
     * 获取全部数据
     */
    public function all() : array
    {
        return array_merge($this->getHandle()->get ?? [], $this->getHandle()->post ?? []);
    }

    /**
     * 获取输入数据
     */
    public function input(string $name, mixed $default = null) : mixed
    {
        return $this->all()[$name] ?? $default;
    }

    /**
     * 获取查询字符串参数
     */
    public function query(string $name = null, mixed $default = null) : mixed
    {
        if (0 === func_num_args()) {
            return array_merge($this->getHandle()->get ?? [], $default ?? []);
        }

        return ($this->getHandle()->get ?? [])[$name] ?? $default;
    }





    /**
     * 获取Http头信息
     */
    public function header(string $name = null) : mixed
    {
        return isset($name) ? ($this->getHandle()->header[$name] ?? null) : ($this->getHandle()->header ?? []);
    }

    /**
     * 获取服务器信息
     */
    public function server(string $name = null) : mixed
    {
        return isset($name) ? ($this->getHandle()->server[$name] ?? null) : ($this->getHandle()->server ?? []);
    }

    /**
     * 获取会话信息Cookie
     */
    public function cookie() : mixed
    {
        return $this->app->cookie;
    }

    /**
     * 获取会话信息Session
     */
    public function session() : mixed
    {
        return $this->app->session;
    }





    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->getHandle()->$method(...$arguments);
    }
}