<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use RuntimeException;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;

/**
 * 路由类
 */
class Route
{
    /**
     * 路由收集器
     */
    protected RouteCollector $collector;

    /**
     * 路由解析器
     */
    protected RouteParser $parser;

    /**
     * 路由制造器
     */
    protected DataGenerator $generator;

    /**
     * 路由分发器
     */
    protected Dispatcher $dispatcher;

    /**
     * 构造函数
     */
    public function __construct(protected array $config, protected array $routeFiles = [])
    {
        // 重置路由
        $this->reset();

        // 读取路由
        $data = $this->hasCacheFile() ? $this->readCacheFile() : $this->readRouteFiles($routeFiles);

        // 写入缓存
        if ($this->enableCache()) {
            $this->writeCacheFile($data);
        }
    }

    /**
     * 路由分发
     */
    public function dispatch(string $method, string $uri) : array
    {
        if (!isset($this->dispatcher)) {
            $this->dispatcher = new $this->config['options']['dispatcher']($this->collector->getData());
        }

        $subfix = $this->config['subfix'] ?? '.html';
        if (!empty($subfix) && str_ends_with($uri, $subfix)) {
            $uri = substr($uri, 0, strlen($uri) - strlen($subfix));
        }

        $result = [];

        $info = $this->dispatcher->dispatch($method, $uri);
        switch ($info[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RuntimeException('Sorry, api not found', 404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $info[1];
                throw new RuntimeException('Sorry, methot not allow', 405);
                break;
            case Dispatcher::FOUND:
                // $handler = $info[1];
                // $vars = $info[2];
                $result = $info[1];
                break;
        }

        return $result;
    }

    /**
     * 重置路由
     */
    public function reset() : static
    {
        $this->collector = new $this->config['options']['routeCollector'](
            $this->parser = new $this->config['options']['routeParser'],
            $this->generator = new $this->config['options']['dataGenerator']
        );

        unset($this->dispatcher);

        return $this;
    }

    /**
     * 读取路由文件
     */
    public function readRouteFiles(?array $files = null) : array
    {
        $files = $files ?? $this->routeFiles;
        foreach ($files as $file) {
            $func = require $file;
            if (!is_callable($func)) {
                throw new RuntimeException('invalid route config file "' . $file . '"');
            }
            $func($this->collector);
        }

        return $this->collector->getData();
    }

    /**
     * 是否启用缓存
     */
    public function enableCache() : bool
    {
        return !$this->config['options']['cacheDisabled'];
    }

    /**
     * 是否存在缓存
     */
    public function hasCacheFile() : bool
    {
        clearstatcache(true, $this->config['options']['cacheFile']);

        return $this->enableCache() && file_exists($this->config['options']['cacheFile']);
    }

    /**
     * 从缓存文件中获取路由数据
     */
    public function readCacheFile() : array
    {
        $data = require $this->config['options']['cacheFile'];
        if (!is_array($data)) {
            throw new RuntimeException('invalid route cache file "' . $this->config['options']['cacheFile'] . '"');
        }

        return $data;
    }

    /**
     * 将路由数据写入缓存文件中
     */
    public function writeCacheFile(array $data) : bool
    {
        if (!is_dir(dirname($this->config['options']['cacheFile']))) {
            mkdir(dirname($this->config['options']['cacheFile']), 0777, true);
        }

        return false !== file_put_contents(
            $this->config['options']['cacheFile'],
            '<?php return ' . var_export($data, true) . ';'
        );
    }
}