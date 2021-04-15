<?php
declare(strict_types=1);

namespace Minimal\Services;

use Monolog\Logger;
use Minimal\Application;
use Minimal\Contracts\Service;

/**
 * 门面服务类
 */
class LoggerService implements Service
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 注册服务
     */
    public function register() : void
    {
        $this->app->set('log', new Logger('SYSTEM'));
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {
        $configs = array_reverse($this->app->config->get('log', []));
        foreach ($configs as $name => $config) {
            $constructor = $config['handler']['constructor'];
            // if (isset($constructor['stream'])) {
            //     $constructor['stream'] = $this->app->logPath($constructor['stream']);
            // } else
            if (isset($constructor['filename'])) {
                $constructor['filename'] = $this->app->logPath($constructor['filename']);
            }
            if (!isset($constructor['level'])) {
                $constructor['level'] = Logger::DEBUG;
            }

            $handler = new $config['handler']['class'](...array_values($constructor));

            $constructor = $config['formatter']['constructor'];
            if (!isset($constructor['format'])) {
                $constructor['format'] = "[%datetime%][%channel%][%level_name%] %message% %context% %extra%\n";
            }
            if (!isset($constructor['dateFormat'])) {
                $constructor['dateFormat'] = 'Y-m-d H:i:s';
            }

            $handler->setFormatter(new $config['formatter']['class'](...array_values($constructor)));

            $this->app->log->pushHandler($handler);
        }
    }
}