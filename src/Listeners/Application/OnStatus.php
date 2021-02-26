<?php
declare(strict_types=1);

namespace Minimal\Listeners\Application;

use Swoole\Process;
use Minimal\Config;
use Minimal\Application;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 状态事件
 * 主进程
 */
#[Listener]
class OnStatus implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app, protected Config $config)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnStatus'];
    }

    /**
     * 是否在运行
     */
    public static function running(string $runtimePath, array $config = null) : bool|int
    {
        // 最终状态
        $status = false;

        // PidFile
        $file = $runtimePath . 'pid';
        clearstatcache(true, $file);
        if (is_file($file)) {
            // 读取文件
            $pid = file_get_contents($file);
            $pid = $pid ? (int) $pid: 0;
            // 进程存在
            if (Process::kill($pid, 0)) {
                $status = $pid;
            }
        }

        // 运行提示
        if (!is_null($config)) {
            if (is_int($status)) {
                echo sprintf(
                    'Server running on %s:%s at %s，Process id: %s',
                    $config['ip'] ??  '0.0.0.0',
                    $config['port'] ??  9501,
                    date('Y-m-d H:i:s'),
                    $pid
                ), PHP_EOL;
            }
        }

        // 在运行中
        return $status;
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 获取配置
        $config = $this->config->get('server');

        // 运行时目录
        $runtimePath = $this->app->getContext()['runtimePath'];

        // 是否在运行
        $pid = self::running($runtimePath, $config);
        if (false === $pid) {
            echo 'Server is not running', PHP_EOL;
        }

        // 返回结果
        return true;
    }
}