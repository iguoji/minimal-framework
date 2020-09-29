<?php
declare(strict_types=1);

namespace App\Validate;

use Minimal\Validate\Validate;

/**
 * 品牌验证器
 */
class Brand extends Validate
{
    /**
     * 字段
     */
    protected array $fields = [
        'id'            =>  [ 'type' => 'int',    'comment' => '编号'       ],
        'sort'          =>  [ 'type' => 'int',    'comment' => '排列顺序'   ],
        'name'          =>  [ 'type' => 'string', 'comment' => '名称'       ],
        'en_name'       =>  [ 'type' => 'string', 'comment' => '英文名称'   ],
        'desc'          =>  [ 'type' => 'string', 'comment' => '备注'       ],
        'en_desc'       =>  [ 'type' => 'string', 'comment' => '英文备注'   ],
        'icon'          =>  [ 'type' => 'string', 'comment' => '图标'       ],
        'created_at'    =>  [ 'type' => 'time',   'comment' => '添加时间'   ],
        'updated_at'    =>  [ 'type' => 'time',   'comment' => '修改时间'   ],
        'deleted_at'    =>  [ 'type' => 'time',   'comment' => '删除时间'   ],
    ];

    /**
     * 规则
     */
    protected array $rules = [
        'name'          =>  [ 'min' => 2, 'max' => 32],
        'en_name'       =>  [ 'min' => 2, 'max' => 64],
        'icon'          =>  [ 'min' => 5, 'max' => 255],
    ];

    /**
     * 默认值
     */
    protected array $defaults = [
        'id'            =>  null,
        'icon'          =>  'https://www.example.com/assets/default.png',
    ];

    /**
     * 保存
     */
    public function save() : array
    {
        // return ['sort' => 'default', 'name' => 'required', 'en_name', 'desc', 'en_desc', 'icon', 'created_at' => 'default'];
        return ['sort' => 'default', 'name', 'en_name', 'desc', 'en_desc', 'icon', 'created_at' => 'default'];
    }

    /**
     * 编辑
     */
    public function edit() : array
    {
        return ['id' => 'required', 'sort', 'name' => 'required', 'en_name', 'desc', 'en_desc', 'icon', 'updated_at' => 'default'];
    }
}