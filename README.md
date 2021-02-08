# 更新日志

+ **[待解决]** 关不掉的 `Swoole` 进程

+ **[待解决]** 日志模块

+ **[待解决]** 数据库返回值需要转换，即便时fetch查询，如果没有值，可能返回false，而不是NULL，这是个问题

+ **[待解决]** 事件的优先级、前置及后置操作

+ **[2021-02-08]** 计划任务

    定义任务类，需实现 `Minimal\Contracts\Task` 接口，并使用 `Task` 注解，如下：
    ```php
    use Minimal\Annotations\Task;
    use Minimal\Contracts\Task as TaskInterface;

    #[Task]
    class MyTask implements TaskInterface
    {
        // 实现接口的各种抽象方法
    }
    ```

    自定义任务跑在 `任务Worker` 中，因此数据库及缓存等对象受到部分功能限制，比如仅有一个主服务器连接

    在 `WebServer` 执行到 `OnWorkerStart` 事件时，将为每一个任务类添加一个毫秒级定时器

    因此，任务类每一次的触发时间，在一开始便已经固定了。

    在定时器中，框架将调用任务类的方法来判断是否要执行任务。

+ **[2021-02-08]** 将 `用户自定义事件` 放入协程容器中运行

    ```php
    \Swoole\Coroutine\run(function() use(命令行参数){
        Application::trigger(string 事件名称, array 命令行参数);
    });
    ```

+ **[2021-02-08]** 全局统一的数据库和缓存初始化，多次初始化会造成连接不够用

    这事分两个情况，分别是 `WebServer` 和其他 `用户自定义事件对象` 需要用到数据库和缓存

    **首先：**

    - 创建 `Database:OnInit` 事件对象，按情况处理配置信息，初始化数据库对象，并绑定到容器

    - 创建 `Cache:OnInit` 事件对象，按情况处理配置信息，初始化缓存对象，并绑定到容器

  **其次：**

    - 针对 `WebServer`，分别是 `普通Worker` 和 `任务Worker`

        只需要在 `Database:OnInit` 和 `Cache:OnInit` 中监听好 `Server:OnWorkerStart` 即可

    - 针对 `用户自定义事件对象`，即非以 `Application`、`Server` 开头的事件

        只需在真正的 `trigger` 事件之前，分别将 `Database:OnInit` 和 `Cache:OnInit` 先行触发一次就好了

        但这个方式不够优雅，目前想象中，应该是在 `用户自定义事件对象` 中按需设置好 `前置需求`

