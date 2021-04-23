<?php
declare(strict_types=1);

namespace Minimal\Foundation;

/**
 * 环境变量类
 */
class Env extends Config
{
    /**
     * 载入数据
     */
    public function load() : void
    {
        $file = $this->app->basePath('.env');
        if (file_exists($file)) {
            $this->dataset = parse_ini_file($file, true);
        }
    }
}