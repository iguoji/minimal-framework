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
    #[Domain]
    #[Route('/brand/save', ['GET', 'post'])]
    public function save($req, $res)
    {
        return [true, time()];
    }

    /**
     * 编辑品牌
     */
    #[Route('/brand/edit', ['put', 'batch'])]
    #[Middleware]
    public function edit($req, $res)
    {
        return [true, time()];
    }
}