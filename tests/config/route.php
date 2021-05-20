<?php

return [
    '*'     =>  [
        '/'     =>  function($req, $res) {
            return 'hello ' . \Minimal\Facades\Db::table('account')->value('username');
        },
        '/redirect' =>  function($req, $res) {
            return $res->redirect('http://www.baidu.com');
        },
        '/file' =>  function($req, $res) {
            return $res->file(dirname(__DIR__) . '/public/logo.png', 'image/png');
        },
    ],
];