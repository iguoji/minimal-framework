<?php
declare(strict_types=1);

namespace Minimal\Contracts;

use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * Http中间件类
 */
interface Middleware
{
    /**
     * 处理程序
     */
    public function handle(Request $req, Response $res, callable $callback) : bool;
}