<?php

return [
    'worker_num'    =>  2,
    'pool'          =>  [
        'master'        =>  150,
        'slave'         =>  0,
    ],
    'default'       =>  [
        'host'          =>  '192.168.2.12',
        'port'          =>  3306,
        'dbname'        =>  'mall_com',
        'username'      =>  'root',
        'password'      =>  '123456',
        'charset'       =>  'utf8mb4',
        'collation'     =>  'utf8mb4_unicode_ci',
    ],
    'cluster'       =>  [
        'master'        =>  [],
        'slave'         =>  [],
    ],
];