<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use RuntimeException;
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
                // method
                if (!is_array($data[0])) {
                    $data[0] = [$data[0]];
                }
                // middlewares
                if (2 === count($data)) {
                    array_push($data, []);
                }
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