<?php
declare(strict_types=1);

namespace Minimal\Contracts;

use Closure;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * 中间件接口
 */
interface Middleware
{
    /**
     * 处理程序
     */
    public function handle(Request $req, Closure $next) : mixed;
}