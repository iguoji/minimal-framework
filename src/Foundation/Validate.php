<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use TypeError;
use Minimal\Support\Type;

/**
 * 验证器
 */
class Validate
{
    /**
     * 数据绑定
     */
    protected array $bindings;

    /**
     * 当前参数
     */
    protected string $current;

    /**
     * 错误消息
     */
    protected string $defaultMessage = '很抱歉、:name不正确！';
    protected array $messages = [
        'require'       =>  '很抱歉、:name必须提供！',
        'in'            =>  '很抱歉、:name只能在[:rule]范围内！',
        'min'           =>  '很抱歉、:name的:unit不能小于:rule！',
        'max'           =>  '很抱歉、:name的:unit不能超过:rule！',

        'alpha'         =>  '很抱歉、:name只能是纯字母！',
        'alphaNum'      =>  '很抱歉、:name只能是字母和数字！',
        'alphaDash'     =>  '很抱歉、:name只能是字母和数字，下划线_及破折号-！',
        'chs'           =>  '很抱歉、:name只能是汉字！',
        'chsAlpha'      =>  '很抱歉、:name只能是汉字、字母！',
        'chsAlphaNum'   =>  '很抱歉、:name汉字、字母和数字！',
        'chsDash'       =>  '很抱歉、:name只能是汉字、字母、数字和下划线_及破折号-',
        'mobile'        =>  '很抱歉、:name格式不正确！',
        'idcard'        =>  '很抱歉、:name格式不正确！',
    ];

    /**
     * 构造函数
     */
    public function __construct(protected array $dataset)
    {
    }





    /**
     * 检测数据
     */
    public function check() : array
    {
        // 最终结果
        $result = [];

        // 循环参数
        foreach ($this->bindings as $name => $item) {
            // 上下文
            $context = ['name' => $item['alias']];

            // 默认值
            if (!isset($this->dataset[$name]) && array_key_exists('default', $item)) {
                $this->dataset[$name] = $item['default'];
            }
            // 必填
            if (!isset($this->dataset[$name]) && !empty($item['require'])) {
                throw new TypeError($this->getMessage('require', $context));
            }
            // 不是必填、也没默认值、而且用户还没提供
            if (!isset($this->dataset[$name])) {
                continue;
            }

            // 过滤
            if (isset($item['rule']['filter'])) {
                foreach ($item['rule']['filter'] as $token => $filter) {
                    $bool = filter_var($this->dataset[$name], $filter[0], $filter[1]);
                    if (false === $bool) {
                        throw new TypeError($this->getMessage($token, $context + $filter[2]));
                    }
                }
            }
            // 正则
            if (isset($item['rule']['regex'])) {
                foreach ($item['rule']['regex'] as $token => $regex) {
                    $bool = 1 === preg_match($regex[0], (string) $this->dataset[$name]);
                    if (false === $bool) {
                        throw new TypeError($this->getMessage($token, $context + $regex[1]));
                    }
                }
            }
            // 回调
            if (isset($item['rule']['call'])) {
                foreach ($item['rule']['call'] as $token => $callback) {
                    $bool = $callback[0]($this->dataset[$name], $this->dataset);
                    if (false === $bool) {
                        throw new TypeError($this->getMessage($token, $context + $callback[1]));
                    }
                }
            }

            // 保存
            $result[$name] = $this->dataset[$name];
        }

        // 返回结果
        return $result;
    }





    /**
     * 绑定参数
     */
    public function bind(string $name, string $type, string $alias) : static
    {
        $this->current = $name;

        $this->type($type);

        if (!is_null($alias)) {
            $this->alias($alias);
        }

        return $this;
    }

    /**
     * 绑定 字符 参数
     */
    public function string(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'string', $alias);
    }

    /**
     * 绑定 整数 参数
     */
    public function int(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'int', $alias);
    }

    /**
     * 绑定 小数 参数
     */
    public function float(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'float', $alias);
    }

    /**
     * 绑定 数值 参数
     */
    public function number(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'number', $alias);
    }

    /**
     * 绑定 布尔 参数
     */
    public function bool(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'bool', $alias);
    }

    /**
     * 绑定 数组 参数
     */
    public function array(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'array', $alias);
    }





    /**
     * 设置类型
     */
    public function type(string $type) : static
    {
        $this->bindings[$this->current]['type'] = $type;

        return $this;
    }

    /**
     * 获取类型
     */
    public function getType() : string
    {
        return $this->bindings[$this->current]['type'];
    }

    /**
     * 设置别名
     */
    public function alias(string $alias) : static
    {
        $this->bindings[$this->current]['alias'] = $alias;

        return $this;
    }

    /**
     * 必填
     */
    public function require(bool $bool = true) : static
    {
        $this->bindings[$this->current]['require'] = $bool;

        return $this;
    }

    /**
     * 默认值
     */
    public function default(mixed $value) : static
    {
        $this->bindings[$this->current]['default'] = $value;

        return $this;
    }





    /**
     * 规则 - 正则表达式
     */
    public function regex(string $pattern, string $token = null, array $context = [], string $message = null) : static
    {
        if (is_null($token)) {
            $token = 'regex' . count($this->bindings[$this->current]['rule']['regex'] ?? []);
        }

        if (!is_null($message)) {
            $this->messages[$token] = $message;
        }

        $this->bindings[$this->current]['rule']['regex'][$token] = [$pattern, $context];

        return $this;
    }

    /**
     * 规则 - 过滤器
     */
    public function filter(int $type, int|array|callable $options = 0, string $token = null, array $context = [], string $message = null) : static
    {
        if (is_null($token)) {
            $token = 'filter' . count($this->bindings[$this->current]['rule']['filter'] ?? []);
        }

        if (!is_null($message)) {
            $this->messages[$token] = $message;
        }

        $this->bindings[$this->current]['rule']['filter'][$token] = [$type, $options, $context];

        return $this;
    }

    /**
     * 规则 - 自定义回调函数验证
     */
    public function call(callable $callback, string $token = null, array $context = [], string $message = null) : static
    {
        if (is_null($token)) {
            $token = 'call' . count($this->bindings[$this->current]['rule']['call'] ?? []);
        }

        if (!is_null($message)) {
            $this->messages[$token] = $message;
        }

        $this->bindings[$this->current]['rule']['call'][$token] = [$callback, $context];

        return $this;
    }





    /**
     *************************
     *      区间范围类
     *************************
     */

    /**
     * 规则 - 数值范围
     */
    public function between(int|float $min, int|float $max) : static
    {
        return $this->min($min)->max($max);
    }

    /**
     * 规则 - 最小数值
     */
    public function min(int|float $num) : static
    {
        return $this->call(function($value) use($num){
            return $value >= $num;
        }, __FUNCTION__, ['rule' => $num, 'unit' => $this->getType() == 'string' ? '长度' : '大小']);
    }

    /**
     * 规则 - 最大数值
     */
    public function max(int|float $num) : static
    {
        return $this->call(function($value) use($num){
            return $value <= $num;
        }, __FUNCTION__, ['rule' => $num, 'unit' => $this->getType() == 'string' ? '长度' : '大小']);
    }

    /**
     * 规则 - 在...范围内
     */
    public function in(...$options) : static
    {
        return $this->call(function($value) use($options){
            return in_array($value, $options);
        }, __FUNCTION__, ['rule' => implode(', ', $options)]);
    }

    /**
     * 规则 - 长度范围
     */
    public function length(int $min, int $max) : static
    {
        return $this->min($min)->max($max);
    }





    /**
     ************************
     *      格式验证类
     ************************
     */

    /**
     * 规则 - 格式 - 邮件地址
     */
    public function email() : static
    {
        return $this->filter(FILTER_VALIDATE_EMAIL, 0, __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 日期格式
     */
    public function date(string $rule = 'Y-m-d H:i:s') : static
    {
        return $this->call(function($value) use($rule){
            $info = date_parse_from_format($rule, $value);
            return 0 == $info['warning_count'] && 0 == $info['error_count'];
        }, __FUNCTION__, ['rule' => $rule]);
    }

    /**
     * 规则 - 格式 - 纯字母
     */
    public function alpha() : static
    {
        return $this->regex('/^[A-Za-z]+$/', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 字母和数字
     */
    public function alphaNum() : static
    {
        return $this->regex('/^[A-Za-z0-9]+$/', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 字母、数字、下划线_及破折号-
     */
    public function alphaDash() : static
    {
        return $this->regex('/^[A-Za-z0-9\-\_]+$/', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 汉字
     */
    public function chs() : static
    {
        return $this->regex('/^[\x{4e00}-\x{9fa5}]+$/u', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 汉字、字母
     */
    public function chsAlpha() : static
    {
        return $this->regex('/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 汉字、字母、数字
     */
    public function chsAlphaNum() : static
    {
        return $this->regex('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 汉字、字母、数字、下划线_及破折号-
     */
    public function chsDash() : static
    {
        return $this->regex('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - URL地址
     */
    public function url() : static
    {
        return $this->filter(FILTER_VALIDATE_URL, 0, __FUNCTION__);
    }

    /**
     * 规则 - 格式 - IP地址
     */
    public function ip() : static
    {
        return $this->filter(FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6, __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 手机号码
     */
    public function mobile() : static
    {
        return $this->regex('/^1[3-9]\d{9}$/', __FUNCTION__);
    }

    /**
     * 规则 - 格式 - 身份证号码
     */
    public function idcard() : static
    {
        return $this->regex('/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/', __FUNCTION__);
    }





    /**
     ************************
     *      字段比较类
     ************************
     */

    /**
     * 规则 - 比较 - 和另一个字段相同
     */
    public function confirm(string $field) : static
    {
        return $this->call(function($value, $values) use($field){
            return $value === $values[$field];
        }, __FUNCTION__);
    }

    /**
     * 规则 - 比较 - 两个字段中必须有一个存在
     */
    public function without(string $field) : static
    {
        // 等待处理：
        return $this->call(function($value, $values) use($field){
            return true;
        }, __FUNCTION__);
    }






    /**
     * 获取数据
     */
    public function getBindings() : array
    {
        return $this->bindings;
    }

    /**
     * 获取信息
     */
    public function getMessage(string $token, array $context = [], string $message = null) : string
    {
        if (is_null($message)) {
            $message = $this->messages[$token] ?? $this->defaultMessage ?? '';
        }

        foreach ($context as $key => $value) {
            $message = str_replace(':' . $key, (string) $value, $message);
        }

        return $message . ' -' . $token;
    }
}