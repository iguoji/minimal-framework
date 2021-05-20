<?php
declare(strict_types=1);

namespace Minimal\Http;

use Throwable;
use think\Template;
use Minimal\Application;

/**
 * 视图类
 */
class View
{
    /**
     * 参数配置
     */
    protected array $config = [
        'view_path'          => '',                     // 模板路径
        'view_suffix'        => 'html',                 // 默认模板文件后缀
        'view_depr'          => DIRECTORY_SEPARATOR,
        'cache_path'         => '',
        'cache_suffix'       => 'php',                  // 默认模板缓存后缀
        'tpl_deny_func_list' => 'echo,exit',            // 模板引擎禁用函数
        'tpl_deny_php'       => false,                  // 默认模板引擎是否禁用PHP原生代码
        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'strip_space'        => false,                  // 是否去除模板文件里面的html空格与换行
        'tpl_cache'          => true,                   // 是否开启模板编译缓存,设为false则每次都会重新编译
        'compile_type'       => 'file',                 // 模板编译类型
        'cache_prefix'       => '',                     // 模板缓存前缀标识，可以动态改变
        'cache_time'         => 0,                      // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
        'layout_on'          => false,                  // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识
        'taglib_begin'       => '{',                    // 标签库标签开始标记
        'taglib_end'         => '}',                    // 标签库标签结束标记
        'taglib_load'        => false,                  // 是否使用内置标签库之外的其它标签库，默认自动检测
        'taglib_build_in'    => 'cx',                   // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'taglib_pre_load'    => '',                     // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
        'display_cache'      => false,                  // 模板渲染缓存
        'cache_id'           => '',                     // 模板缓存ID
        'tpl_replace_string' => [],
        'tpl_var_identify'   => 'array',                // .语法变量识别，array|object|'', 为空时自动识别
        'default_filter'     => 'htmlentities',         // 默认过滤方法 用于普通标签输出
    ];

    /**
     * 当前引擎
     */
    protected mixed $engine;

    /**
     * 渲染后的内容
     */
    protected string $content;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 模板路径
        $this->config['view_path'] = $app->viewPath() . DIRECTORY_SEPARATOR;
        // 缓存路径
        $this->config['cache_path'] = $app->cachePath('view') . DIRECTORY_SEPARATOR;
        // 合并配置
        $this->config = array_merge($this->config, $app->config->get('view', []));
        // 实例化引擎
        $this->engine = new Template($this->config);
    }

    /**
     * 解析和获取模板内容输出
     */
    public function fetch(string $template = '', array $vars = []): static
    {
        $this->getContent(function () use ($vars, $template) {
            $this->engine->fetch($template, $vars);
        });

        return $this;
    }

    /**
     * 根据字符串内容渲染输出
     */
    public function display(string $content, array $vars = []): static
    {
        $this->getContent(function () use ($vars, $content) {
            $this->engine->display($content, $vars);
        });

        return $this;
    }

    /**
     * 获取模板引擎渲染内容
     */
    protected function getContent(callable $callback): string
    {
        // 页面缓存
        ob_start();
        ob_implicit_flush(false);

        // 渲染输出
        try {
            $callback();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        // 返回结果
        return $this->content = ob_get_clean();
    }

    /**
     * 输出内容
     */
    public function __toString() : string
    {
        return $this->content;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {
        return $this->engine->$method(...$arguments);
    }
}