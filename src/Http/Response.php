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
            'message'   =>  $message ?? ($code == 200 ? '恭喜您、操作成功！' : '很抱歉、操作失败！'),
            'data'      =>  $data,
        ], $context);
        $this->content(json_encode($result, JSON_UNESCAPED_UNICODE));

        return $this;
    }

    /**
     * 返回Html
     */
    public function html(array|string $filename = null, array $params = []) : static
    {
        // 参数整理
        if (is_array($filename)) {
            $params = $filename;
            $filename = null;
        }
        if (is_null($filename)) {
            $route = $this->app->request->getRoute();
            if (!is_array($route)) {
                throw new Exception('很抱歉、请提供静态页面路径！');
            }
            $filename = strtolower($route[0]::class);
            $array = explode('\\', $filename);
            $filename = implode(DIRECTORY_SEPARATOR, array_splice($array, 2));
        }

        // 来自于重定向的参数
        $path = $this->app->request->path();
        if ($this->app->session->has($path)) {
            $redirectParams = $this->app->session->get($path);
            $redirectParams = json_decode($redirectParams, true);
            $params = array_merge($params, $redirectParams);
            $this->app->session->delete($path);
        }

        // 最终内容
        $context = $filename;

        // 按静态页面渲染
        $filename = $this->app->viewPath($filename . '.html');
        if (is_file($filename)) {
            $context = $this->app->view->content($filename, $params);
        }

        // 设置内容
        $this->getHandle()->header('Content-Type', 'text/html;charset=utf-8');
        $this->content((string) $context);

        // 返回结果
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

        $code = $th->getCode() ?: 500;
        $message = $th->getMessage();
        $data = method_exists($th, 'getData') ? $th->getData() : [];

        if ($code == 302 && !empty($data)) {
            return $this->redirect($data[0], [
                'exception' =>  [$code, $message, '']
            ]);
        }

        return $this->json($data, $code, $message, $context);
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
        $this->app->context->set('response:end:sendfile', $filename);

        return $this;
    }

    /**
     * 页面跳转
     */
    public function redirect(string $url, array $data = [], int $http_code = 302) : static
    {
        if (!empty($data)) {
            $data = array_map(function($item){
                return json_encode($item, JSON_UNESCAPED_UNICODE);
            }, $data);
            $this->app->request->session->set($url, json_encode($data, JSON_UNESCAPED_UNICODE), 60);
        }

        $this->app->context->set('response:end:redirect', [$url, $http_code]);

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
            // 按情况处理
            if ($this->app->context->has('response:end:sendfile')) {
                // 文件输出
                $this->getHandle()->sendfile($this->app->context->get('response:end:sendfile'));
            } else if ($this->app->context->has('response:end:redirect')) {
                // 重定向
                $this->getHandle()->redirect(...$this->app->context->get('response:end:redirect'));
            } else {
                // 内容
                $html = $html ?: $this->getContent();
                // 输出响应
                $this->getHandle()->end($html);
            }
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