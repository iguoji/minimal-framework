<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Support\Type;
use Minimal\Foundation\Exception;

/**
 * 验证器
 */
class Validate
{
    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 数据仪表盘
     */
    protected array $dashboard = [];

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
        'confirm'       =>  '很抱歉、:name必须和:field保持一致！',
        'with'          =>  '很抱歉、当:name存在时:field也必须提供！',
        'without'       =>  '很抱歉、:name或:field至少需要提供一个！',

        'in'            =>  '很抱歉、:name只能在[:rule]范围内！',
        'min'           =>  '很抱歉、:name的大小不能小于:rule！',
        'max'           =>  '很抱歉、:name的大小不能超过:rule！',
        'length_min'    =>  '很抱歉、:name的长度不能小于:rule！',
        'length_max'    =>  '很抱歉、:name的长度不能超过:rule！',

        'digit'         =>  '很抱歉、:name只能是[0-9]组成的纯数字！',
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
            // 检查单项
            if (!is_null($value = $this->value($name))) {
                // 仅校验
                if (!empty($item['unset'])) {
                    continue;
                }
                // 保存数据
                $result[$name] = $value;
            }
        }
        // 返回结果
        return $result;
    }

    /**
     * 检查单个
     */
    public function value(string $name = null) : mixed
    {
        // 获取数据
        $name = $name ?? $this->current;
        $item = $this->bindings[$name];

        // 上下文
        $context = ['name' => $item['alias']];

        // 默认值
        if (!isset($this->dataset[$name]) && array_key_exists('default', $item)) {
            $this->dataset[$name] = is_callable($item['default']) ? $item['default']($this->dataset) : $item['default'];
        }

        // 字段比较
        if (isset($item['confirm'])) {
            $value = $this->dataset[$name] ?? null;
            foreach ($item['confirm'] as $field) {
                if (!isset($this->dataset[$field]) || $value !== $this->dataset[$field]) {
                    $context['field'] = $field;
                    throw new Exception($this->getMessage('confirm', $context));
                }
            }
        }

        // 字段宿主在，我必在
        if (isset($item['with']) && isset($this->dataset[$name])) {
            $values = array_filter($this->dataset, fn($v, $k) => in_array($k, $item['with']), ARRAY_FILTER_USE_BOTH);
            if (empty($values) || count($item['with']) != count($values)) {
                $context['field'] = $item['with'];
                throw new Exception($this->getMessage('with', $context));
            }
        }

        // 字段多选一
        if (isset($item['without'])) {
            $values = array_filter($this->dataset, fn($v, $k) => in_array($k, $item['without']), ARRAY_FILTER_USE_BOTH);
            if (empty($values) && !isset($this->dataset[$name])) {
                $context['field'] = $item['without'];
                throw new Exception($this->getMessage('without', $context));
            }
        }

        // 必填
        if (!isset($this->dataset[$name]) && !empty($item['require'])) {
            throw new Exception($this->getMessage('require', $context));
        }

        // 不是必填、也没默认值、而且用户还没提供
        if (!isset($this->dataset[$name]) || (is_string($this->dataset[$name]) && !strlen($this->dataset[$name]))) {
            return null;
        }

        // 过滤
        if (isset($item['rule']['filter'])) {
            foreach ($item['rule']['filter'] as $token => $filter) {
                $bool = filter_var($this->dataset[$name], $filter[0], $filter[1]);
                if (false === $bool) {
                    throw new Exception($this->getMessage($token, $context + $filter[2]));
                }
            }
        }
        // 正则
        if (isset($item['rule']['regex'])) {
            foreach ($item['rule']['regex'] as $token => $regex) {
                $bool = 1 === preg_match($regex[0], (string) $this->dataset[$name]);
                if (false === $bool) {
                    throw new Exception($this->getMessage($token, $context + $regex[1]));
                }
            }
        }
        // 回调
        if (isset($item['rule']['call'])) {
            foreach ($item['rule']['call'] as $token => $callback) {
                $bool = $callback[0]($this->dataset[$name], $this->dataset);
                if (false === $bool) {
                    throw new Exception($this->getMessage($token, $context + $callback[1]));
                }
            }
        }

        // 返回数据
        return Type::transform($this->dataset[$name], $item['type']);
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
        return $this->bind($name, 'string', $alias ?? $name);
    }

    /**
     * 绑定 整数 参数
     */
    public function int(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'int', $alias ?? $name);
    }

    /**
     * 绑定 小数 参数
     */
    public function float(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'float', $alias ?? $name);
    }

    /**
     * 绑定 数值 参数
     */
    public function number(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'number', $alias ?? $name);
    }

    /**
     * 绑定 布尔 参数
     */
    public function bool(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'bool', $alias ?? $name);
    }

    /**
     * 绑定 数组 参数
     */
    public function array(string $name, string $alias = null) : static
    {
        return $this->bind($name, 'array', $alias ?? $name);
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
     * 必填 - 多个字段中必须有一个存在
     */
    public function requireWithout(string ...$fields) : static
    {
        $this->bindings[$this->current]['without'] = $fields;

        return $this;
    }

    /**
     * 必填 - 当指定的字段存在时，必填
     */
    public function requireWith(string ...$fields) : static
    {
        $this->bindings[$this->current]['with'] = $fields;

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
     * 仅校验，不保存
     */
    public function unset(bool $bool = true) : static
    {
        $this->bindings[$this->current]['unset'] = $bool;

        return $this;
    }





    /**
     * 规则 - 正则表达式
     */
    public function regex(string $pattern, string $token = null, array $context = [], string $message = null) : static
    {
        if (is_null($token)) {
            $this->dashboard[__FUNCTION__] = $this->dashboard[__FUNCTION__] ?? 0;
            $token = __FUNCTION__ . $this->dashboard[__FUNCTION__]++;
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
            $this->dashboard[__FUNCTION__] = $this->dashboard[__FUNCTION__] ?? 0;
            $token = __FUNCTION__ . $this->dashboard[__FUNCTION__]++;
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
            $this->dashboard[__FUNCTION__] = $this->dashboard[__FUNCTION__] ?? 0;
            $token = __FUNCTION__ . $this->dashboard[__FUNCTION__]++;
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
        }, __FUNCTION__, ['rule' => $num]);
    }

    /**
     * 规则 - 最大数值
     */
    public function max(int|float $num) : static
    {
        return $this->call(function($value) use($num){
            return $value <= $num;
        }, __FUNCTION__, ['rule' => $num]);
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
    public function length(int $min = null, int $max = null) : static
    {
        if (!is_null($min)) {
            $this->call(function($value) use($min){
                return mb_strlen($value) >= $min;
            }, 'length_min', ['rule' => $min]);
        }

        if (!is_null($max)) {
            $this->call(function($value) use($max){
                return mb_strlen($value) <= $max;
            }, 'length_max', ['rule' => $max]);
        }

        return $this;
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
     * 规则 - 格式 - 纯数字
     */
    public function digit() : static
    {
        return $this->call(function($value){
            return ctype_digit((string) $value);
        }, __FUNCTION__);
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
     * 规则 - 比较 - 和别的字段一致
     */
    public function confirm(string ...$fields) : static
    {
        $this->bindings[$this->current]['confirm'] = $fields;

        return $this;
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

        if (isset($context['field'])) {
            if (is_array($context['field'])) {
                $context['field'] = implode('、', array_map(fn($f) => $this->bindings[$f]['alias'] ?? $f, $context['field']));
            } else {
                $context['field'] = $this->bindings[$context['field']]['alias'] ?? $context['field'];
            }
        }

        foreach ($context as $key => $value) {
            $message = str_replace(':' . $key, (string) $value, $message);
        }

        return $message;
    }
}