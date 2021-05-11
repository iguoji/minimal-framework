<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;

/**
 * 响应类
 */
class Response
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
        $this->app->context->set(__CLASS__, $object);

        return $this;
    }

    /**
     * 获取句柄
     */
    public function getHandle() : mixed
    {
        return $this->app->context->get(__CLASS__);
    }

    /**
     * 响应内容
     */
    public function content(string $data) : void
    {
        $this->app->context->set('response:content', $data);
    }

    /**
     * 获取响应内容
     */
    public function getContent() : string
    {
        return $this->app->context->get('response:content', '');
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->getHandle()->$method(...$arguments);
    }
}