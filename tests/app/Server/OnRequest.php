<?php
declare(strict_types=1);

namespace App\Server;

use Minimal\Application;

class OnRequest
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 请求处理
     */
    public function handle(\Swoole\Http\Request $request, \Swoole\Http\Response $response) : void
    {
        $this->app->log->info(__CLASS__ . ':' . __FUNCTION__);

        $response->end(time());
    }
}