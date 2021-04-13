<?php
declare(strict_types=1);

use Minimal\Facades\Env;
use Minimal\Facades\Config;

/**
 * 获取环境变量
 */
if (!function_exists('env')) {
    function env(string $key = null, mixed $default = null) : mixed
    {
        return is_null($key)
            ? Env::all()
            : Env::get($key, $default);
    }
}

/**
 * 获取配置文件
 */
if (!function_exists('config')) {
    function config(string $key = null, mixed $default = null) : mixed
    {
        return is_null($key)
            ? Config::all()
            : Config::get($key, $default);
    }
}