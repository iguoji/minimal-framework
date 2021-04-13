<?php
declare(strict_types=1);

namespace Minimal\Services;

use Minimal\Application;
use Minimal\Contracts\Service;
use Symfony\Component\Console\Application as Console;

/**
 * 控制台服务类
 */
class ConsoleService implements Service
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 注册服务
     */
    public function register() : void
    {
        $console = new Console('Minimal Framework', Application::VERSION);
        $this->app->set('console', $console);
    }

    /**
     * 启动服务
     */
    public function boot() : void
    {
        $commands = $this->scanClass(dirname(__DIR__) . '/Commands/', 'Minimal\\Commands\\');

        if ($this->app->has('config')) {
            $userCommands = $this->app->config->get('commands', []);
            $commands = array_merge($commands, $userCommands);
        }

        foreach ($commands as $key => $common) {
            $this->app->console->add(
                $this->app->make($common)
            );
        }
    }

    /**
     * 获取指定目录下的PHP类
     */
    public function scanClass(string $folder, string $namespace) : array
    {
        $classes = [];
        $paths = glob($folder . '*', GLOB_MARK);
        foreach ($paths as $path) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
            if (is_dir($path)) {
                $classes = array_merge($classes, $this->scanClass($path, $namespace . $filename . '\\'));
            } else {
                $classes[] = $namespace . $filename;
            }
        }
        return $classes;
    }
}