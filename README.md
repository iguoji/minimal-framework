## 路由
> /config/route.php
```php
<?php

return [
    '*'     =>  [
        '/one'     =>  \App\Open\Wechat::class, // 只给类的话，那么默认调用该类的 handle 方法
        '/two'     =>  [\App\Open\Wechat::class, 'index'],
        '/debug'   =>  function($req, $res) {},
    ],
];
```

## 控制器
> /app/Admin/Test.php

```php
<?php

namespace App\Admin;

use Minimal\Http\Request;
use Minimal\Http\Response;

class Test
{
    public function handle(Request $req, Response $res)
    {
        return 'world';
    }

    public function debug($req, $res)
    {
        return 'world';
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


# 想法

## 1. 框架

框架主要提供 `容器`、`日志`、`环境变量`、`配置文件`、`事件`、`Facade` 等核心功能

像 `路由`、`数据库`、`缓存`、`验证器`、`Http服务器`、`请求`、`响应` 等功能都属于额外按需加载的组件

例如，框架将核心功能准备好了之后，开发者通过命令行调用 `Server 服务器` 的 `start` 开始方法将开启一个 `web` 服务器

在该 `web` 服务器中，将会加载 `数据库`、`缓存`、`路由` 等常用功能组件

## 2. 命令行

> 命令行调用的是容器或类中对象的方法

调用容器中对象的方法

```bash
php minimal server start
```

调用指定类的方法
```bash
php minimal Minimal\\Support\\Str random
```

## 3. 事件

