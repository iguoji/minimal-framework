<?php

return [
    'subfix'                    =>  '.html',
    'options'                   =>  [
        'routeParser'           =>  \FastRoute\RouteParser\Std::class,
        'dataGenerator'         =>  \FastRoute\DataGenerator\GroupCountBased::class,
        'dispatcher'            =>  \FastRoute\Dispatcher\GroupCountBased::class,
        'routeCollector'        =>  \FastRoute\RouteCollector::class,
        'cacheDisabled'         =>  false,
        'cacheFile'             =>  '../runtime/cache/route.php'
    ]
];