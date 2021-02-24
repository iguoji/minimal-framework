# 更新日志

+ **[待解决]** 将路由功能从 `Application` 类中拆分出去

+ **[待解决]** 日志模块

+ **[待解决]** 数据库返回值需要转换，即便时fetch查询，如果没有值，可能返回false，而不是NULL，这是个问题

+ **[2021-02-24]** 关不掉的 `Swoole` 进程，task worker 无法及时退出，可能在执行计时任务的原因

    是因为启动了Timer定时器，但没有正确的关闭

    框架做了判断，`if ($workerId == $server->setting['worker_num']) `

    只有任务进程才会去执行定时器任务，因此定时器处于 `Task Worker` 中

    正常来说，关闭这个定时器，需要在 `OnWorkerStop` 中执行 `Timer::clearAll()` 即可

    但是不行，非得在 `OnWorkerExit` 中执行才有效，但是官方又说 `Task Worker` 不会执行 `OnWorkerExit` 事件

    搞不懂，不过能解决就暂时这样了

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

