<?php
declare(strict_types=1);

namespace App\Open;

/**
 * 品牌类
 */
#[Validate(\App\Validate\Brand::class)]
class Brand
{
    /**
     * 保存品牌
     */
    #[Route('/brand/save')]
    public function save($req, $res)
    {
        return $req;
    }

    /**
     * 编辑品牌
     */
    #[Route('/brand/edit')]
    public function edit($req, $res)
    {
        return $req;
    }
}