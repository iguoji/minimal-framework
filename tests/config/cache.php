<?php

return [
    'host'      =>  env('cache.redis.host', '127.0.0.1'),
    'port'      =>  env('cache.redis.port', 6379),
    'timeout'   =>  2.5,
    'auth'      =>  ['123456'],
];