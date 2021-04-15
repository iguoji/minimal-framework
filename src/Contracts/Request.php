<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 请求接口类
 */
interface Request
{
    /**
     * 获取服务器信息
     */
    public function server(string $key, mixed $default = null) : mixed;

    /**
     * 获取所有服务器信息
     */
    public function servers() : array;

    /**
     * 是否存在服务器信息
     */
    public function hasServer(string $key) : bool;


    /**
     * 获取头部信息
     */
    public function header(string $key, mixed $default = null) : mixed;

    /**
     * 获取所有头部信息
     */
    public function headers() : array;

    /**
     * 是否存在头部信息
     */
    public function hasHeader(string $key) : bool;


    /**
     * 获取参数
     */
    public function param(string $key, mixed $default = null) : mixed;

    /**
     * 获取所有参数
     */
    public function params() : array;

    /**
     * 是否存在参数
     */
    public function hasParam(string $key) : bool;
}