<?php
declare(strict_types=1);

namespace Minimal\Services;

use ErrorException;
use Minimal\Application;
use Minimal\Contracts\Service;

/**
 * 错误服务类
 */
class ErrorService implements Service
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 服务注册
     * 主绑定容器，最好不要调用其他服务
     */
    public function register() : void
    {
        // 绑定错误
        set_error_handler(function($errno, $message, $file, $line){
            throw new ErrorException($message, 0, $errno, $file, $line);
        });
        // 捕获异常
        set_exception_handler(function($th){
            $logger = null;
            if ($this->app->has('log')) {
                $logger = $this->app->get('log');
            }
            if (is_null($logger)) {
                echo '[ ' . date('Y-m-d H:i:s') . ' ] ' . __CLASS__ . PHP_EOL;
                echo 'Messgae::' . $th->getMessage() . PHP_EOL;
                echo 'File::' . $th->getFile() . PHP_EOL;
                echo 'Line::' . $th->getLine() . PHP_EOL;
                echo PHP_EOL;
            } else {
                $logger->error($th->getMessage(), [
                    'File'   =>  $th->getFile(),
                    'Line'   =>  $th->getLine(),
                ]);
            }
        });
    }

    /**
     * 服务启动
     * 所有服务注册完成后依次启动
     */
    public function boot() : void
    {

    }
}