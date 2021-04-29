<?php
declare(strict_types=1);

namespace Minimal\Foundation;

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
        $array = $app->config->get('route', []);
        foreach ($array as $domain => $group) {
            foreach ($group as $rule => $data) {
                // 请求方法 GET|POST，必须传数组
                if (!is_array($data[0])) {
                    $data[0] = [$data[0]];
                }

                // 中间件
                if (2 === count($data)) {
                    array_push($data, []);
                }
                if (!is_array($data[2])) {
                    $data[2] = [$data[2]];
                }
                $data[2] = array_map(function($mid) use($app){
                    if (is_string($mid)) {
                        $mid = [$mid, 'handle'];
                    }
                    if (is_array($mid) && is_string($mid[0])) {
                        $mid[0] = $app->make($mid[0]);
                    }
                    return $mid;
                }, $data[2]);

                // 保存数据
                $group[$rule] = $data;
            }

            $domains = explode('|', $domain);
            foreach ($domains as $key => $value) {
                $this->bindings[$value] = $group;
            }
        }
    }

    /**
     * 路由匹配
     */
    public function dispatch(string $domain, string $method, string $uri) : mixed
    {
        $group = $this->bindings[$domain] ?? $this->bindings['*'] ?? [];
        if (isset($group[$uri]) && in_array($method, $group[$uri][0])) {
            return array_slice($group[$uri], 1);
        }

        return null;
    }
}