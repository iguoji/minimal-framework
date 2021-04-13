<?php

return [
    // 默认服务器
    'default'                       =>  [
        'class'                     =>  \Swoole\WebSocket\Server::class,
        'constructor'               =>  [
            'host'                  =>  '0.0.0.0',
            'port'                  =>  8080,
        ],
        'hook_flags'                =>  null,
        'settings'                  =>  [
            'worker_num'            =>  swoole_cpu_num(),
            'task_worker_num'       =>  swoole_cpu_num(),
            'task_enable_coroutine' =>  true,
            'daemonize'             =>  true,
            'log_file'              =>  '../runtime/log/swoole.log',
            'pid_file'              =>  '../runtime/pid',
            'reload_async'          =>  true,
            'enable_coroutine'      =>  true,
            'stats_file'            =>  '../runtime/status',
            'document_root'         =>  '../public',
            'enable_static_handler' =>  true,
        ],
        'callbacks'                 =>  [
            // 'onRequest'             =>  [\App\Server\OnRequest::class, 'handle'],
        ],
    ],
];