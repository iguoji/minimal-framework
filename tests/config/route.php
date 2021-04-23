<?php

return [
    '*'     =>  [
        '/'     =>  [['GET'], [\App\Open\Wechat::class, 'index']],
    ],
];