<?php
declare(strict_types=1);

namespace App\Open;

use Minimal\Annotations\Route;
use Minimal\Annotations\Domain;
use Minimal\Annotations\Middleware;

/**
 * 品牌类
 */
#[Middleware([\App\Middleware\Auth::class, \App\Middleware\Auth1::class])]
#[Domain(['www.baidu.com', 'dev.baidu.com', '*.my.baidu.com'])]
#[Test([\App\Middleware\Auth::class])]
class Brand
{
    /**
     * 保存品牌
     */
    #[Domain(['a.baidu.com', 'dev.baidu.com'])]
    #[Route('/brand/save', ['get', 'post'])]
    #[Event([\App\Middleware\Test::class])]
    public function save($req, $res)
    {
        return [true, time()];
    }

    /**
     * 编辑品牌
     */
    #[Route('/brand/edit', ['put', 'batch'])]
    #[Event([\App\Middleware\Auth2::class])]
    #[Middleware]
    public function edit($req, $res)
    {
        return [true, time()];
    }
}