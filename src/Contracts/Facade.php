<?php
declare(strict_types=1);

namespace Minimal\Contracts;

use Minimal\Container;

/**
 * 门面类
 */
abstract class Facade
{
    /**
     * 容器对象
     */
    protected static Container $container;

    /**
     * 注册容器
     */
    public static function setContainer(Container $container)
    {
        self::$container = $container;
    }

    /**
     * 调用方法
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $class = static::getClass();
        $instance = self::$container->get($class);
        return call_user_func([$instance, $method], ...$arguments);
    }

    /**
     * 获取类名
     */
    abstract static function getClass() : string;
}