<?php
declare(strict_types=1);

use Minimal\Facades\Env;
use Minimal\Facades\Config;
use Minimal\Facades\Request;

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

/**
 * 分页
 */
if (!function_exists('pagination')) {
	function pagination(string|int $total, string|int $size, string|int $pageNo = null) : string
	{
		// 参数处理
		$pageNo = $pageNo ?? Request::query('pageNo', 1);
		$pageNo = max($pageNo, 1);							// 当前页码
		$totalPages = ceil($total / $size);					// 最大页数
		$prevNo = max($pageNo - 1, 1);						// 上一页
		$nextNo = min($pageNo + 1, $totalPages);			// 下一页
		$startIndex = ($pageNo - 1) * $size;				// 当前第一条记录的索引
		$endIndex = min($startIndex + $size - 1, $total);	// 当前最后一条记录的索引
		$queryParams = Request::query();
		$url = function($pageNo) use($queryParams){
			$queryParams['pageNo'] = $pageNo;
			return '?' . http_build_query($queryParams);
		};

		// 组织代码
		$html = '';
		$html .= '<p class="m-0 text-muted">当前是第 <span>' . ($startIndex + 1) . '</span> 到 <span>' . ($endIndex + 1) . '</span> 条记录，共有 <span>' . $total . '</span> 条记录</p>';
		$html .= '<ul class="pagination m-0 ms-auto">';
			$html .= '<li class="page-item' . ($pageNo == 1 ? ' disabled' : '') . '">';
				$html .= '<a class="page-link" href="' . $url($prevNo) . '">';
					$html .= '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>';
					$html .= '上一页';
				$html .= '</a>';
			$html .= '</li>';
			// 1
			// 		1, 2, 3, 4, 5
			// 2
			// 		1, 2, 3, 4, 5
			// 3
			// 		1, 2, 3, 4, 5
			// 4
			// 		2, 3, 4, 5, 6
			// 5
			// 		3, 4, 5, 6, 7
			$startPageNo = max(1, $pageNo - 2);
			$endPageNo = max(5, $pageNo + 2);

			for ($no = $startPageNo;$no <= $totalPages && $no <= $endPageNo; $no++) {
				$html .= '<li class="page-item' . ($no == $pageNo ? ' active' : '') . '"><a class="page-link" href="' . $url($no) . '">' . $no . '</a></li>';
			}
			// $html .= '<li class="page-item"><a class="page-link" href="#">1</a></li>';
			// $html .= '<li class="page-item active"><a class="page-link" href="#">2</a></li>';
			// $html .= '<li class="page-item"><a class="page-link" href="#">3</a></li>';
			// $html .= '<li class="page-item"><a class="page-link" href="#">4</a></li>';
			// $html .= '<li class="page-item"><a class="page-link" href="#">5</a></li>';
			$html .= '<li class="page-item' . ($pageNo == $totalPages ? ' disabled' : '') . '">';
				$html .= '<a class="page-link" href="' . $url($nextNo) . '">';
					$html .= '下一页 <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>';
				$html .= '</a>';
			$html .= '</li>';
		$html .= '</ul>';

		// 返回代码
		return $html;
	}
}
