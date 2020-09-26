<?php
declare(strict_types=1);

namespace App\Open;

/**
 * 品牌类
 */
#[Middleware([\App\Middleware\Auth::class, \App\Middleware\Auth1::class])]
#[Domain(['www.baidu.com', 'dev.baidu.com', '*.my.baidu.com'])]
#[Test([\App\Middleware\Auth::class])]
#[Validate(\App\Validate\Brand::class)]
class Brand
{
    /**
     * 保存品牌
     */
    #[Domain]
    #[Route('/brand/save', ['GET', 'POst'])]
    public function save($req, $res)
    {
        return [true, time(), $req];
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