<?php
declare(strict_types=1);

namespace App\Open;

use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Queue;
use Minimal\Foundation\Validate;

class Wechat
{
    public function index(\Swoole\Http\Request $req)
    {
        $validate = new Validate($req->get ?? []);
        $validate->int('id', '编号')->require()->default(111)->between(100, 5000);
        $validate->int('type', '类型')->between(-1, 1)->in(-1, 0, 1);
        $validate->string('username', '账号')->require()->alphaNum()->length(6, 18);
        $validate->string('email', '邮箱')->length(6, 18)->email();
        $validate->string('ip', 'IP地址')->require()->length(6, 18)->ip();
        $validate->string('realname', '真实姓名')->length(2, 4)->chs();
        $validate->string('idcard', '身份证号码')->require()->length(15, 18)->idcard();
        $validate->bool('rememberme', '记住我')->require();



        return [$validate->getBindings(), $validate->check()];


        if (rand(1, 3) == 2) {
            Queue::task([
                \App\Task\Email::class,
                'handle',
                rand(1000000, 9999999) . '@qq.com',
                '恭喜您、注册成功！',
            ], function(){
                Log::debug('我是任务处理完成之后的回调函数');
            });
        }
        return Db::table('account')->where('zone', 81)->where('phone', 13000000002)->inc('money');
    }

    public function debug()
    {
        $account = Db::table('account')->first();
        return $account;
        return 'hello ' . time();
    }
}