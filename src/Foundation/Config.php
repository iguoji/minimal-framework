<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;
use Minimal\Support\Collection;

/**
 * 配置类
 */
class Config extends Collection
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        $files = glob($app->configPath('*.php'));
        foreach ($files as $file) {
            $this->dataset[pathinfo($file, PATHINFO_FILENAME)] = require_once $file;
        }
    }
}