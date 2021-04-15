<?php
declare(strict_types=1);

namespace App\Open;

use Minimal\Facades\Db;

class Wechat
{
    public function debug()
    {
        $account = Db::table('account')->first();
        return $account;
        return 'hello ' . time();
    }
}