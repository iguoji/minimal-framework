<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 请求事件
 */
#[Listener]
class OnRequest implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct()
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnRequestBefore', 'Application:OnRequest', 'Application:OnRequestAfter'];
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 前置事件
        if ($event == 'Application:OnRequestBefore') {
            return $this->onBefore(...$arguments);
        }
        // 后置事件
        if ($event == 'Application:OnRequestAfter') {
            return $this->onAfter(...$arguments);
        }
        // 返回结果
        return true;
    }

    /**
     * 前置事件
     */
    public function onBefore($req, $res) : bool
    {
        // Favicon
        if (($req->server['request_uri'] ?? $req->server['path_info']) == '/favicon.ico') {
            $res->end();
            return false;
        }

        // 返回结果
        return true;
    }

    /**
     * 后置事件
     */
    public function onAfter($req, $res) : bool
    {
        // 返回结果
        return true;
    }
}