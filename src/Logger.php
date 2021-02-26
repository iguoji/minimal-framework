<?php
declare(strict_types=1);

namespace Minimal;

use Psr\Log\LoggerInterface;

/**
 * 日志
 */
#[Inject(alias: 'log')]
class Logger
{
    /**
     * 数据源
     */
    private $dataset = [];

    /**
     * 构造函数
     */
    public function __construct(Config $configure)
    {
        // 获取配置
        $config = array_merge([

        ], $configure->get('log', []));

        //
    }

    /**
     * 获取驱动
     */
    public function driver(string $name = null) : LoggerInterface
    {
        return $this->channel[$name] ?? $this->channel[$name] = new Monolog();
    }

    /**
     * 处理程序
     */
    public function __call(string $name, array $arguments)
    {
        return $this->driver()->$name(...$arguments);
    }
}