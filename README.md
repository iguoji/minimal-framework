## 路由
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

## 控制器
> /app/Admin/Test.php

```php
<?php

namespace App\Admin;

use Swoole\Http\Request;
use Swoole\Http\Response;

class Test
{
    public function hello(Request $req, Response $res)
    {
        return 'world';
    }

    public function debug($req, $res)
    {
        return 'world';
    }
}
```

## 中间件
> /app/Middleware/Token.php

```php
<?php

namespace App\Admin;

use Closure;
use Swoole\Http\Request;
use Minimal\Contracts\Middleware

class Test implements Middleware
{
    public function handle(Request $req, Closure $next)
    {
        return $next($req);
    }
}
```

## 页面模板
> /app/View/aaa/bbb/index.html

来源于：https://github.com/top-think/think-template
可参考：https://www.kancloud.cn/manual/think-template/1286412

# 更新说明

+ **[2021-05-05]** 数据库功能已改好，为了简约强行在一个类里实现所有功能还是不够明知，所以将数据库分离出去单独开发成一个包，同时决定了，先以`Swoole`为基础来做，等后续再做成开放式的。

+ **[2021-04-30]** 数据库功能还有待完善，比如表连接时无法使用闭包实现多个条件，以及想简单使用一个`where`并获取其`sql`等细节功能都发现了问题。

+ **[2021-04-23]** 反反复复几个版本来回推翻修改，目前这一版全部功能基本完成，后续通过实战一个项目来修复细节BUG，时刻还是需要谨记框架名称 **minimal** 的由来，那就是一个简单、轻量、通俗的微型框架，适合于各类中小应用，适用于初学及熟练型技术人员使用。