<?php
declare(strict_types=1);

namespace Minimal\Http;

use Throwable;
use Minimal\Application;
use Minimal\Support\File;
use Minimal\Foundation\Exception;

/**
 * 响应类
 */
class Response
{
    /**
     * 构造方法
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 设置句柄
     */
    public function setHandle(mixed $object) : static
    {
        $this->app->context->set(__CLASS__, $object);

        return $this;
    }

    /**
     * 获取句柄
     */
    public function getHandle() : mixed
    {
        return $this->app->context->get(__CLASS__);
    }

    /**
     * 设置响应内容
     */
    public function content(string $data) : void
    {
        $this->app->context->set('response:content', $data);
    }

    /**
     * 获取响应内容
     */
    public function getContent() : string
    {
        return $this->app->context->get('response:content', '');
    }

    /**
     * 返回Json
     */
    public function json(mixed $data, string|int $code = 200, string $message = null, array $context = []) : static
    {
        $this->getHandle()->header('Content-Type', 'application/json;charset=utf-8');

        $result = array_merge([
            'code'      =>  $code,
            'data'      =>  $data,
            'messgae'   =>  $message ?? ($code == 200 ? '恭喜您、操作成功！' : '很抱歉、操作失败！'),
        ], $context);
        $this->content(json_encode($result));

        return $this;
    }

    /**
     * 返回Html
     */
    public function html(mixed $data) : static
    {
        $this->getHandle()->header('Content-Type', 'text/html;charset=utf-8');
        $this->content((string) $data);

        return $this;
    }

    /**
     * 返回异常
     */
    public function exception(Throwable $th) : static
    {
        $context = [];
        if (!empty($this->app->env->get('app.debug'))) {
            $context = [
                'file'      =>  $th->getFile(),
                'line'      =>  $th->getLine(),
                'trace'     =>  $th->getTrace(),
            ];
        }
        return $this->json(
            method_exists($th, 'getData') ? $th->getData() : [],
            $th->getCode() ?: 500,
            $th->getMessage(),
            $context
        );
    }

    /**
     * 返回文件
     */
    public function file(string $filename, string $mime = null) : static
    {
        if (!is_file($filename)) {
            throw new Exception('file not exists: ' . $filename);
        }

        $this->getHandle()->header('Content-Type', $mime ?? File::mimeType($filename));
        $this->getHandle()->sendfile($filename);

        return $this;
    }

    /**
     * 页面跳转
     */
    public function redirect(string $url, int $http_code = 302) : static
    {
        $this->getHandle()->redirect($url, $http_code);

        return $this;
    }

    /**
     * 结束响应
     */
    public function end(string $html = '') : void
    {
        if ($this->getHandle()->isWritable()) {
            // 状态码
            $this->getHandle()->status(200);
            // Cookie
            $cookies = $this->app->cookie->all();
            foreach ($cookies as $key => $cookie) {
                $this->getHandle()->cookie(...array_values($cookie));
            }
            // 内容
            $html = $html ?: $this->getContent();
            // 输出响应
            $this->getHandle()->end($html);
        }
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->getHandle()->$method(...$arguments);
    }
}