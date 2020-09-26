<?php
declare(strict_types=1);

namespace Minimal;

use RuntimeException;
use InvalidArgumentException;

/**
 * 验证器
 */
class Validate
{
    /**
     * 字段
     */
    protected array $fields = [];

    /**
     * 规则
     */
    protected array $rules = [];

    /**
     * 默认值
     */
    protected array $defaults = [];

    /**
     * 信息
     */
    protected array $messages = [
        'required'  =>  '很抱歉、:attribute不能为空！'
    ];

    /**
     * 检测数据
     */
    public function check(array $parameter, array $fields) : array
    {
        // 最终数据
        $data = [];
        // 循环字段
        foreach ($fields as $field => $ruleStr) {
            // 没有规则
            if (is_int($field)) {
                $field = $ruleStr;
                $ruleStr = '';
            }
            // 参数的值
            $value = $parameter[$field] ?? null;
            // 类型验证
            if (!is_null($value) && !$this->verifyType($field, $value)) {
                $message = $this->getMessage($field, 'type');
                throw new InvalidArgumentException($message);
            }
            // 类型转换
            if (!is_null($value)) {
                $value = $this->transform($value, $this->getType($field));
            }
            // 自定义或特殊规则
            $rules = $this->parseRule($ruleStr);
            // 合并数据类型规则
            $rules = array_merge($rules, $this->getRule($field));
            // 验证类型
            foreach ($rules as $rule => $args) {
                // 不存在的规则
                if (!method_exists($this, $rule)) {
                    throw new RuntimeException(sprintf('unknown validate rule "%s"', $rule));
                }
                // 参数调整
                if (!is_array($args)) {
                    $args = [$args];
                }
                array_unshift($args, $value);
                // 提供了值或是必填，但是规则验证又不通过
                if ((!is_null($value) || $rule == 'required') && !$this->$rule(...$args)) {
                    $message = $this->getMessage($field, $rule);
                    throw new InvalidArgumentException($message);
                }
            }
            // 保存数据
            $data[$field] = $value ?? $this->getDefault($field);
        }
        // 返回数据
        return $data;
    }

    /**
     * 类型转换
     */
    public function transform(mixed $value, string $type) : mixed
    {
        switch($type)
        {
            case 'int':
            case 'time':
                return (int) $value;
                break;
            case 'string':
                return (string) $value;
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * 必填字段
     */
    public function required(mixed $value) : bool
    {
        return !is_null($value);
    }

    /**
     * 最小长度|数值
     */
    public function min(mixed $value, int $condition = 0) : bool
    {
        if (is_int($value)) {
            return $value >= $condition;
        } else if (is_string($value)) {
            return strlen($value) >= $condition;
        }
        return false;
    }

    /**
     * 最大长度|数值
     */
    public function max(mixed $value, int $condition = 0) : bool
    {
        if (is_int($value)) {
            return $value <= $condition;
        } else if (is_string($value)) {
            return strlen($value) <= $condition;
        }
        return false;
    }

    /**
     * 在指定范围内
     */
    public function in(mixed $value, array $array) : bool
    {
        return in_array($value, $array);
    }

    /**
     * 获取类型
     */
    public function getType(string $field) : string
    {
        return $this->fields[$field]['type'] ?? 'string';
    }

    /**
     * 类型验证
     */
    public function verifyType(string $field, mixed $value) : bool
    {
        switch($this->getType($field)) {
            case 'int':
            case 'time':
                return filter_var($value, FILTER_VALIDATE_INT) === false ? false : true;
                break;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) === false ? false : true;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * 根据字符串解析规则
     */
    public function parseRule(string $str) : array
    {
        $rules = [];
        $str = trim($str);
        if (!strlen($str)) {
            return $rules;
        }
        $arr = array_map(fn($s) => trim($s), explode('|', $str));
        foreach ($arr as $str1) {
            [$name, $argsStr] = strpos($str1, ':') ? explode(':', $str1) : [$str1, null];
            $rules[$name] = is_null($argsStr) ? [] : array_map(fn($s) => trim($s), explode(',', $argsStr));
        }
        return $rules;
    }

    /**
     * 获取规则
     */
    public function getRule(string $field) : array
    {
        return $this->rules[$field] ?? [];
    }

    /**
     * 获取默认值
     */
    public function getDefault(string $field) : mixed
    {
        $value = $this->defaults[$field] ?? null;
        if (is_callable($value)) {
            $value = $value();
        }
        if (is_null($value)) {
            switch ($this->getType($field)) {
                case 'int':
                case 'float':
                    $value = 0;
                    break;
                case 'string':
                    $value = '';
                    break;
                case 'time':
                    $value = time();
                    break;
            }
        }
        return $value;
    }

    /**
     * 获取备注
     */
    public function getComment(string $field) : string
    {
        return $this->fields[$field]['comment'] ?? $field;
    }

    /**
     * 获取信息
     */
    public function getMessage(string $field, string $rule) : string
    {
        $key = sprintf("%s.%s", $field, $rule);
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        } else {
            return sprintf('verifier check "%s" failed', $key);
        }
    }
}