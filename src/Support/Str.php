<?php
declare(strict_types=1);

namespace Minimal\Support;

/**
 * 字符串
 */
class Str
{
    /**
     * 按指定字符进行切割成数组（不区分大小写）
     */
    public static function split(string $delimiter, string $string) : array
    {
        $array = [];
        $start = 0;
        $index = 0;
        $length = strlen($delimiter);
        while (false !== $index = stripos($string, $delimiter, $index)) {
            $array[] = substr($string, $start, $index);
            $start = $index;
            $index += $length;
        }
        $array[] = !$start ? $string : substr($string, $start + $length);
        return $array;
    }

    /**
     * 按指定字符切割后通过回调函数进行处理并再次返回拼接后的字符串
     */
    public static function map(string $string, callable $callback, string $delimiter = '') : string
    {
        $pieces = self::split($delimiter, $string);
        $pieces = Arr::each($callback, $pieces);
        return implode($delimiter, $pieces);
    }
}