<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Swoole\Process;
use Minimal\Config;
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
    public function __construct(protected Config $config)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnStatus'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 获取配置
        $config = $this->config->get('server');

        // 基础目录
        $basePath = $arguments['context']['basePath'] . DIRECTORY_SEPARATOR;

        // PidFile
        $file = $basePath . 'pid';
        // 没有文件
        if (!is_file($file)) {
            echo '很抱歉、服务器尚未运行！', PHP_EOL;
            return false;
        }
        // 读取文件
        $pid = file_get_contents($file);
        $pid = $pid ? (int) $pid: 0;
        // 没有启动
        if (!Process::kill($pid, 0)) {
            echo '很抱歉、服务器尚未启动！', PHP_EOL;
            return false;
        }

        echo sprintf(
            'Server running on %s:%s at %s，Process id: %s',
            $config['ip'] ?? '0.0.0.0',
            $config['port'] ?? 9501,
            date('Y-m-d H:i:s'),
            $pid
        ), PHP_EOL;

        // 返回结果
        return true;
    }
}