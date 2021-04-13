<?php

return [
    // 驱动句柄
    'handle'    =>  'file',
    // 驱动配置
    'file'      =>  [
        // 'path'      =>  '../runtime/cache',
    ],
    'redis'     =>  [
        'host'      =>  env('cache.redis.host', '127.0.0.1'),
        'port'      =>  env('cache.redis.port', 6379),
        'timeout'   =>  2.5,
        'auth'      =>  ['123456'],
    ]
];