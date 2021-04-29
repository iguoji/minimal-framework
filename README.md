# 路由
> /config/route.php
```php
<?php

return [
    '*'     =>  [
        '/one'     =>  [['GET'],            [\App\Open\Wechat::class, 'index'],    \App\Middleware\One::class],
        '/two'     =>  [['GET', 'POST'],    [\App\Open\Wechat::class, 'index'],    [\App\Middleware\One::class, \App\Middleware\Two::class]],
        '/debug'   =>  [['GET'],            [\App\Open\Wechat::class, 'index'],    [[\App\Other\Test::class, 'handle']]],
    ],
];
```

# 更新说明

+ **[2021-04-23]** 反反复复几个版本来回推翻修改，目前这一版全部功能基本完成，后续通过实战一个项目来修复细节BUG，时刻还是需要谨记框架名称 **minimal** 的由来，那就是一个简单、轻量、通俗的微型框架，适合于各类中小应用，适用于初学及熟练型技术人员使用。