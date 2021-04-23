<?php
declare(strict_types=1);

namespace App\Task;

use Minimal\Application;
use Minimal\Facades\Log;

/**
 * 任务类
 */
class Email
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * 处理程序
     */
    public function handle(string $mail, string $title) : bool
    {
        Log::debug('我是Email任务类：' . time(), [
            $mail, $title
        ]);

        return true;
    }
}