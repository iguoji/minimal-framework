<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;

/**
 * 日志类
 */
class Log
{
    /**
     * 错误级别(从高到低)
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * 日志配置
     */
    protected int $fileSize = 1024 * 1024 * 2;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * System is unusable.
     */
    public function emergency(string $message, array $context = []) : void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public function alert(string $message, array $context = []) : void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical(string $message, array $context = []) : void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error(string $message, array $context = []) : void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning(string $message, array $context = []) : void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice(string $message, array $context = []) : void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     */
    public function info(string $message, array $context = []) : void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug(string $message, array $context = []) : void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log(string $level, string $message, array $context = []) : void
    {
        try {
            // 信息处理
            $message = '[' . date('Y-m-d H:i:s') . '][' . $level . '] ' . $message . PHP_EOL;
            if (!empty($context)) {
                $message .= var_export($context, true) . PHP_EOL;
            }

            // 终端输出
            if (!empty($this->app->env->get('app.debug', false))) {
                fwrite(STDIN, $message);
            }


            // 文件输出
            if (true === $this->app->env->get('log.enable', false)) {
                error_log($message, 3, $this->path(date('Y/m/d') . '.log'));
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * 整理目录
     */
    public function path(string $filename) : string
    {
        $path = $this->app->logPath($filename);

        if (!is_dir(dirname($path))) {
            echo $path, PHP_EOL;
            mkdir(dirname($path), 0777, true);
        }

        if (is_file($filename) && filesize($filename) >= $this->fileSize) {
            rename($path, $path . '.' . microtime(true));
        }

        return $path;
    }
}