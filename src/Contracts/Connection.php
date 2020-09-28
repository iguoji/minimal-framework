<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 数据库连接接口
 */
interface Connection
{
    /**
     * 构造函数
     * 接收数据库配置
     */
    public function __construct(array $config);

    /**
     * 连接驱动
     */
    public function connect(bool $reconnect = true);

    /**
     * 释放连接
     * 连接池释放连接时调用
     */
    public function release();
}