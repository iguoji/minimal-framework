<?php
declare(strict_types=1);

namespace Minimal\Support;

/**
 * 数组
 */
class Arr
{
    /**
     * 导出数组
     */
    public static function export(array $array, $space = 4) : string
    {
        // 最长Key
        $maxLen = array_reduce(array_keys($array), function($last, $curr){
            return $last > strlen($curr) ? $last : strlen($curr);
        }, 0);
        // 最终结果
        $result = $space == 4 ? "[\r\n" : '';
        // 循环数组
        foreach ($array as $key => $value) {
            // 间隔
            $result .= str_repeat(' ', $space);
            // Key
            $result .= "'$key'";
            // 间隔
            // echo $maxLen . ' : ' . strlen($key) . ' : ' . $space . PHP_EOL;
            $result .= str_repeat(' ', $maxLen - strlen($key) + 4);
            // =>
            $result .= "=> ";
            // 子元素
            if (is_array($value)) {
                // 子元素 - 开始
                $result .= "[\r\n";
                // 继续递归
                $result .= self::export($value, $space + 4);
                // 间隔
                $result .= str_repeat(' ', $space);
                // 子数组 - 结束
                $result .= "],\r\n";
            } else {
                // 根据具体类型处理格式
                switch(gettype($value)) {
                    case 'bool':
                    case 'boolean':
                        $result .= $value ? 'true' : 'false';
                        break;
                    case 'int':
                    case 'integer':
                    case 'float':
                    case 'double':
                    case 'number':
                        $result .= $value;
                        break;
                    case 'NULL':
                    case 'null':
                        $result .= 'null';
                        break;
                    default:
                        $result .= "'$value'";
                        break;
                }
                // 结尾用逗号
                $result .= ",\r\n";
            }
        }
        $result .= $space == 4 ? "]" : '';
        // 返回结果
        return $result;
    }

    /**
     * 获取所有Key
     */
    public static function array_keys_recursive(array $array) : array
    {
        $result = [];
        foreach($array as $key => $value) {
            $result[] = $key;
            if (is_array($value)) {
                $result = self::array_merge_recursive_distinct($result, self::array_keys_recursive($value));
            }
        }
        return $result;
    }

    /**
     * 递归合并数组
     */
    public static function array_merge_recursive_distinct(array $array1, array $array2, ...$arrays) : array
    {
        array_unshift($arrays, $array1, $array2);
        $merged = array();
        while ($arrays) {
            $array = array_shift($arrays);
            if (!is_array($array)) {
                $array = [$array];
            }
            if (!$array) {
                continue;
            }
            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                        $merged[$key] = static::array_merge_recursive_distinct($merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * 是否为数组类型
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * 键值是否存在
     */
    public static function exists($array, $key)
    {
        // 数组实现对象
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        // 普通数组
        return array_key_exists($key, $array);
    }

    /**
     * 是否存在指定Key
     * @param   $array    array   数据源
     * @param   $keys     arrat   Key(支持通过.访问下级)，可通过数组查询多个Key
     * @return  bool      Key都存在或其中某个Key不存在
     */
    public static function has($array, $key) : bool
    {
        // 默认设置为有多个Key
        $keys = (array) $key;
        // 错误参数
        if (! $array || $keys === []) {
            return false;
        }
        // 循环多个Key
        foreach ($keys as $subKey) {
            // 复制一份数据源
            $subKeyArray = $array;
            // 存在则跳过去判断下一个Key
            if (static::exists($array, $subKey)) {
                continue;
            }
            // 将Key通过.分隔
            $segments = explode('.', $subKey);
            foreach ($segments as $segment) {
                // 是数组型并存在当前Key
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    // 更换数据源
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    // 不存在则统一返回false
                    return false;
                }
            }
        }
        // 检查通过
        return true;
    }

    /**
     * 获取数据
     * @param $array    array   数据源
     * @param $key      string  Key(支持通过.访问下级)
     * @param $default  mixed   默认值(null)
     */
    public static function get($array, $key, $default = null)
    {
        // 必须是数组
        if (! static::accessible($array)) {
            return $default;
        }
        // 没有Key
        if (is_null($key)) {
            return $array;
        }
        // 直接命中Key，立即返回数据
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        // 非多维Key，直接返回结果或默认值
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }
        // 循环多维Key访问数组
        $keys = explode('.', $key);
        foreach ($keys as $segment) {
            // 是数组 并且 存在Key 就替换数据源
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                // 也许多次.之后没有找到Key，返回默认值
                return $default;
            }
        }
        // 返回结果
        return $array;
    }

    /**
     * 设置数据
     * @param $array    array   数据源
     * @param $key      string  Key(支持通过.访问下级)
     * @param $value    mixed   数据
     */
    public static function set(&$array, $key, $value)
    {
        // 没有Key，直接更改数据源
        if (is_null($key)) {
            return $array = $value;
        }
        // 分隔Key
        $keys = explode('.', $key);
        // 循环处理Key
        while (count($keys) > 1) {
            // 删除并得到第一个Key
            $key = array_shift($keys);
            // 不存在该Key
            if (! static::exists($array, $key)) {
                $array[$key] = [];
            }
            // 该Key对应的值不是数组
            if (! static::accessible($array[$key])) {
                // 不管原值是什么，直接改成空数组，有待商榷
                $array[$key] = [];
            }
            // 调整引用数组为子数组
            $array = &$array[$key];
        }
        // 针对最后一个Key赋值
        $array[array_shift($keys)] = $value;
        // 返回数组
        return $array;
    }

    /**
     * 文件夹排序
     */
    public function directorySort(&$array)
    {
        return usort($array, function($one, $two){
            $oneScore = File::isDirectory($one) ? 2 : 1;
            $twoScore = File::isDirectory($two) ? 2 : 1;
            if ($oneScore == $twoScore) {
                if ($one > $two) {
                    $oneScore++;
                } else if ($two > $one) {
                    $twoScore++;
                }
            }
            return $oneScore <=> $twoScore;
        });
    }
}