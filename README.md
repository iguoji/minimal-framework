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

底层：`容器`、`日志`、`环境变量`、`配置文件`、`事件`、`Facade`

中层：`Http服务器`、`数据库`、`缓存`

外层：`请求`、`响应`、`路由`、`验证器`、`会话`

在一个正常的Http请求生命周期过程中，上述三者依次开启

例如：

开发者通过命令行调用 `Server 服务器` 的 `start`，框架首先完成底层核心功能

随后调用 `start` 方法开启一个 `web` 服务器，在各类监听事件中完成中层功能

当用户的Http请求到达后，在监听的事件中完成外层功能

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

## 4. 会话

会话ID

Cookie：str64
Header: str64

通过Cookie或是Header传递，如果没有且需要则可自行创建

会话ID存储于当前请求的上下文中

会话数据存储于缓存Redis中

## 5. 身份

### 5.1 初始化

优先级：Cookie > Header

Cookie: session_id => secret
```php
$secret = $req->cookie('session_id');
```
Header: Authorization => Session secret
```php
$string = $req->header('Authorization');
list($prefix, $secret) = explode(' ', $string);
```

### 5.2 保存

```php
// 参数1: 身份名称
// 参数2: 身份数据
// 参数3: 有效秒杀

// 例子1：管理员身份
$req->session->set('admin', [], 60 * 60 * 24);

// 例子2：普通用户身份
$req->session->set('account', [], 60 * 60 * 24 * 7);
```