<?php
declare(strict_types=1);

namespace Minimal\Support;

/**
 * 类型
 */
class Type
{
    /**
     * 是否为整数
     */
    public static function isInt(mixed $number) : bool
    {
        if (is_int($number)) {
            return true;
        } else if (is_string($number)) {
            return false !== filter_var($number, FILTER_VALIDATE_INT);
        } else {
            return false;
        }
    }

    /**
     * 转为整数
     */
    public static function int(mixed $number) : array|int
    {
        if (is_scalar($number)) {
            return (int) $number;
        } else if (is_array($number)) {
            return array_map(fn($n) => self::int($n), $number);
        } else {
            return false;
        }
    }

    /**
     * 是否为小数
     */
    public static function isFloat(mixed $number) : bool
    {
        if (is_int($number) || is_float($number)) {
            return true;
        } else if (is_string($number)) {
            return false !== filter_var($number, FILTER_VALIDATE_FLOAT);
        } else {
            return false;
        }
    }

    /**
     * 转为小数
     */
    public static function float(mixed $number, int $decimals = 2) : array|float
    {
        if (is_scalar($number)) {
            return (float) number_format((float) $number, $decimals, '.', '');
        } else if (is_array($number)) {
            return array_map(fn($n) => self::float($n, $decimals), $number);
        } else {
            return 0;
        }
    }

    /**
     * 是否为布尔
     */
    public static function isBool(mixed $value) : bool
    {
        if (is_bool($value)) {
            return true;
        } else if (is_int($value) && in_array($value, [0, 1])) {
            return true;
        } else if (is_string($value) && in_array(strtolower($value), ['0', '1', 'true', 'false'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 转为布尔
     */
    public static function bool(mixed $value) : bool
    {
        if (in_array(strtolower($value), ['0', 'false'])) {
            return false;
        } else {
            return (bool) $value;
        }
    }

    /**
     * 是否为字符串
     */
    public static function isString(mixed $value) : bool
    {
        return is_string($value);
    }

    /**
     * 转为字符串
     */
    public static function string(mixed $value) : null|array|string
    {
        if (is_scalar($value)) {
            return strtolower($value) == 'null' ? null : (string) $value;
        } else if (is_array($value)) {
            return array_map(fn($s) => self::string($s), $value);
        } else {
            return '';
        }
    }

    /**
     * 是否为数组
     */
    public static function isArray(mixed $value) : bool
    {
        return is_array($value);
    }

    /**
     * 转为数组
     */
    public static function array(mixed $value) : array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * 类型转换
     */
    public static function transform(mixed $value, string $type) : mixed
    {
        if (is_array($value) && in_array($type, ['int', 'float', 'bool', 'string'])) {
            return array_map(fn($v) => self::transform($v, $type), $value);
        }
        switch ($type) {
            case 'int':
                return self::int($value);
                break;
            case 'float':
                return self::float($value);
                break;
            case 'bool':
                return self::bool($value);
                break;
            case 'string':
                return self::string($value);
                break;
            case 'array':
                return self::array($value);
                break;
            default:
                return $value;
                break;
        }
    }
}