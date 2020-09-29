<?php
declare(strict_types=1);

namespace App\Open;

use Minimal\Facades\Db;

/**
 * 品牌类
 */
#[Validate(\App\Validate\Brand::class)]
class Brand
{
    /**
     * 保存品牌
     */
    #[Route('/brand/save', ['GET', 'POST'])]
    public function save($req, $res)
    {
        return [
            Db::query('SELECT * FROM `brand` WHERE `id` = ?', [1]),
            Db::first('SELECT * FROM `brand` WHERE `id` = ?', [2]),
            Db::number('SELECT COUNT(*) FROM `brand`'),
            Db::number('SELECT SUM(`id`) FROM `brand`'),
            Db::number('SELECT SUM(`id`) AS `count` FROM `brand`'),
        ];
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