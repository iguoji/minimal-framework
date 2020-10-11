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
            Db::beginTransaction(),
            Db::execute('INSERT INTO `brand` VALUES(?, ?, ?, ?, ?, ?, ?, ?)', [null, 0, '标题', 'BiaoTi', '描述', 'MiaoSu', 'icon', date('Y-m-d H:i:s')]),
            Db::beginTransaction(),
            Db::execute('INSERT INTO `brand` VALUES(?, ?, ?, ?, ?, ?, ?, ?)', [null, 0, '标题2', 'BiaoTi', '描述', 'MiaoSu', 'icon', date('Y-m-d H:i:s')]),
            Db::rollBack(),
            Db::execute('INSERT INTO `brand` VALUES(?, ?, ?, ?, ?, ?, ?, ?)', [null, 0, '标题3', 'BiaoTi', '描述', 'MiaoSu', 'icon', date('Y-m-d H:i:s')]),
            Db::commit(),
            Db::query('SELECT * FROM `brand` WHERE `id` = ?', [1]),
            Db::first('SELECT * FROM `brand` WHERE `id` = ?', [2]),
            Db::value('SELECT COUNT(*) FROM `brand`'),
            Db::value('SELECT SUM(`id`) FROM `brand`'),
            Db::value('SELECT SUM(`id`) AS `count` FROM `brand`'),
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