<?php

return [
    // 默认处理器
    'default'               =>  [
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