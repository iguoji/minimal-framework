<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;
use Minimal\Support\Collection;

/**
 * 环境变量类
 */
class Env extends Collection
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        $file = $app->basePath('.env');
        if (file_exists($file)) {
            $this->dataset = parse_ini_file($file, true);
        }
    }
}