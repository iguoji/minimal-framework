<?php
declare(strict_types=1);

namespace Minimal\Support;

/**
 * 数组类
 */
class Arr
{
    /**
     * 数组深度
     */
    public static function depth(array $array, string|int $subKey = null) : int
    {
        $index = 0;
        foreach ($array as $value) {
            if (!is_null($subKey)) {
                $value = $value[$subKey] ?? [];
            }
            if (is_array($value) && !empty($value)) {
                $index = max($index, static::depth($value) + 1);
            }
        }
        return $index;
    }

    /**
     * 遍历数组
     */
    public static function each(callable $callback, array $array) : array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $callback($value, $key);
        }
        return $array;
    }

    /**
     * 是否存在指定Key
     */
    public static function has(array $array, array|string $key) : bool
    {
        $keys = (array) $key;
        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $subKey) {
            $subKeyArray = $array;
            if (isset($array[$subKey])) {
                continue;
            }

            $segments = explode('.', $subKey);
            foreach ($segments as $segment) {
                if (is_array($subKeyArray) && isset($subKeyArray[$segment])) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 获取数据
     */
    public static function get(array $array, string $key = null, mixed $default = null) : mixed
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        $keys = explode('.', $key);
        foreach ($keys as $segment) {
            if (isset($array[$segment])) {
                $array = $array[$segment];
                if (!is_array($array)) {
                    break;
                }
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 设置数据
     */
    public static function set(array $array, string $key = null, mixed $value = null) : mixed
    {
        if (is_null($key)) {
            return $array = $value;
        }

        if (strpos($key, '.') === false) {
            return $array[$key] = $value;
        }

        $keys = explode('.', $key);
        $count = count($keys);
        foreach ($keys as $index => $segment) {
            if ($index === $count - 1) {
                return $array[$segment] = $value;
            }

            if (! isset($array[$segment])) {
                $array[$segment] = [];
            }

            if (! is_array($array[$segment])) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        return $value;
    }
}