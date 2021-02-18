<?php
declare(strict_types=1);

namespace Minimal\Facades;

use RuntimeException;
use Psr\Container\ContainerInterface;

/**
 * 门面类
 */
abstract class Facade
{
    /**
     * 容器对象
     */
    protected static ContainerInterface $container;

    /**
     * 注册容器
     */
    public static function setContainer(ContainerInterface $container) : void
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
        $instance = self::$container->get($class);
        // 获取存在的属性
        if (str_starts_with($method, 'get') && strlen($method) > 3 && !method_exists($instance, $method)) {
            $property = strtolower(substr($method, 3));
            if (property_exists($instance, $property)) {
                return $instance->$property;
            }
        }
        // 设置存在的属性
        if (str_starts_with($method, 'set') && strlen($method) > 3 && !method_exists($instance, $method)) {
            $property = strtolower(substr($method, 3));
            if (property_exists($instance, $property)) {
                return $instance->$property = $arguments[0] ?? null;
            }
        }
        // 返回结果
        return call_user_func([$instance, $method], ...$arguments);
    }

    /**
     * 获取类名
     */
    abstract static function getClass() : string;
}