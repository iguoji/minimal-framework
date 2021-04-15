<?php

return [
    // 默认日志
    'default'               =>  [
        // 具体处理器
        'handler'           =>  [
            'class'         =>  \Monolog\Handler\StreamHandler::class,
            'constructor'   =>  [
                'stream'    =>  'php://stderr',
            ],
        ],
        // 格式化程序
        'formatter'         =>  [
            'class'         =>  \Monolog\Formatter\LineFormatter::class,
            'constructor'   =>  [
                'format'                =>  "[%datetime%][%channel%][%level_name%] %message% %context% %extra%\n",
                'dateFormat'            =>  'Y-m-d H:i:s',
                'allowInlineLineBreaks' =>  false,
            ],
        ],
    ],
    // 文件日志
    'rotating'              =>  [
        // 具体处理器
        'handler'           =>  [
            'class'         =>  \Monolog\Handler\RotatingFileHandler::class,
            'constructor'   =>  [
                'filename'      =>  'app.log',
                'maxFiles'      =>  30,
                'level'         =>  \Monolog\Logger::DEBUG,
            ]
        ],
        // 格式化程序
        'formatter'         =>  [
            'class'         =>  \Monolog\Formatter\LineFormatter::class,
            'constructor'   =>  [
                'format'                =>  null,
                'dateFormat'            =>  null,
                'allowInlineLineBreaks' =>  false,
            ]
        ]
    ],
];