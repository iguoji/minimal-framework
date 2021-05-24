<?php
declare(strict_types=1);

namespace Minimal\Http;

use Throwable;
use Minimal\Application;
use Minimal\Support\Manager;

/**
 * 视图类
 */
class View extends \think\Template
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 模板路径
        $config['view_path'] = $app->viewPath() . DIRECTORY_SEPARATOR;
        // 缓存路径
        $config['cache_path'] = $app->cachePath('view') . DIRECTORY_SEPARATOR;
        // 合并配置
        $config = array_merge($config, $app->config->get('view', []));

        // 父类构造
        parent::__construct($config);
    }

    /**
     * 获取最终解析出的内容
     */
    public function content(string $template, array $vars = []): string
    {
        // 页面缓存
        ob_start();
        if (PHP_VERSION > 8.0) {
            ob_implicit_flush(false);
        } else {
            ob_implicit_flush(0);
        }

        // 获取内容
        $this->data = [];
        $this->fetch($template, $vars);

        // 返回结果
        return ob_get_clean();
    }
}