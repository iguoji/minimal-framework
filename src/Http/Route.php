<?php
declare(strict_types=1);

namespace Minimal\Http;

use Minimal\Application;

/**
 * 路由类
 */
class Route
{
    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 获取路由
        $routes = $app->config->get('route', []);
        // 循环路由
        foreach ($routes as $domain => $group) {
            // 按域名分组循环
            foreach ($group as $rule => $data) {
                // 按情况处理
                if (is_string($data)) {
                    // 如果是字符串，标识为Class
                    $object = $app->make($data);
                    $method = 'handle';
                    $data = [$object, $method];
                } else if (is_array($data) && count($data) >= 2) {
                    // 如果是数组，且元素大于等于2，标识第一个为类，第二个为函数名
                    $object = $app->make($data[0]);
                    $method = $data[1];
                    $data = [$object, $method];
                }

                // 保存数据
                $group[$rule] = $data;
            }

            // 拆分域名保存
            $domains = explode('|', $domain);
            foreach ($domains as $key => $value) {
                $this->bindings[$value] = $group;
            }
        }
    }

    /**
     * 路由匹配
     */
    public function dispatch(string $domain, string $uri) : mixed
    {
        // 按域名获取路由
        $group = $this->bindings[$domain] ?? $this->bindings['*'] ?? [];

        // 过滤后缀
        if (str_ends_with($uri, '.html')) {
            $uri = substr($uri, 0, -5);
        }

        // 返回结果
        return $group[$uri] ?? null;
    }
}