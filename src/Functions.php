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

/**
 * Ajax请求
 */
if (!function_exists('ajax')) {
	function ajax(string $url, string $method = 'get', array $data = [], array $header = [], int $timeout = 2) : mixed
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (!empty($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		$res = curl_exec($ch);
		if ($error = curl_errno($ch)) {
			echo curl_error($ch), PHP_EOL;
		}
		curl_close($ch);
		return $res;
	}
}