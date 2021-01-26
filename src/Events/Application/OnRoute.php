<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 路由事件
 */
#[Listener]
class OnRoute implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnRoute'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 启动服务
        $router = $this->app->getRouter();
        // 路由列表
        $routes = $router['routes'] ?? [];
        // 域名列表
        $domains = $router['domains'] ?? [];
        // 循环路由
        $tableData = [['Rule', 'Methods', 'Controller', 'Action', 'Validate', 'Middlewares', 'Domains']];
        foreach ($routes as $route) {
            $tableData[] = [
                $route['path'],
                implode(', ', $route['methods']),
                $route['class'],
                $route['method'],
                isset($route['validate']) ? $route['validate']::class : '',
                implode(', ', $route['middlewares']),
                implode(', ', $route['domains']),
            ];
        }
        // 输出表格
        echo $this->table($tableData);
        // 返回结果
        return true;
    }

    /**
     * 输出表格
     */
    public function table(array $data) : string
    {
        // 列最长字符
        $colLenth = [];
        foreach ($data as $tr) {
            foreach ($tr as $key => $td) {
                $colLenth[$key] = max($colLenth[$key] ?? 0, strlen($td));
            }
        }
        // 最终字符串
        $output = '';
        // 线
        $hr = '';
        // 打印数据
        foreach ($data as $line => $tr) {
            $trOutput = '| ';
            if (0 === $line) {
                $hr .= '+-';
            }
            foreach ($tr as $key => $td) {
                if (0 === $line) {
                    $hr .= str_repeat('-', $colLenth[$key] + 8);
                    $hr .= '+-';
                }

                $trOutput .= str_pad($td, $colLenth[$key], ' ');
                $trOutput .= str_repeat(' ', 8);
                $trOutput .= '| ';
            }
            $hr = rtrim($hr, '-');
            if (0 === $line) {
                $output .= $hr;
                $output .= PHP_EOL;
            }
            $output .= $trOutput;
            $output .= PHP_EOL;
            if (0 === $line) {
                $output .= $hr;
                $output .= PHP_EOL;
            }
        }
        $output .= $hr;
        $output .= PHP_EOL;
        // 返回字符串
        return $output;
    }
}