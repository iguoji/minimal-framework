<?php

return [
    'ip'            =>  '0.0.0.0',
    'port'          =>  8080,
    'setting'       =>  [
        'worker_num'    =>  swoole_cpu_num(),
        'reload_async'  =>  true,
        'max_wait_time' =>  60,
        'pid_file'      =>  '/home/wwwroot/minimal/framework/tests/pid',
    ]
];