<?php
declare(strict_types=1);

namespace Minimal\Foundation;

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
    public static function setContainer(Container $container) : void
    {
        self::$container = $container;
    }

    /**
     * 调用方法
     */
    public static function __callStatic(string $method, array $arguments) : mixed
    {
        // 获取实例
        $class = static::getClass();
        $instance = static::isShare() ? self::$container->get($class) : self::$container->make($class);
        // 返回结果
        return call_user_func([$instance, $method], ...$arguments);
    }

    /**
     * 是否共享复用
     */
    protected static function isShare() : bool
    {
        return true;
    }

    /**
     * 获取类名
     */
    abstract static function getClass() : string;
}