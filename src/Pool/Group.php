<?php
declare(strict_types=1);

namespace Minimal\Pool;

use Throwable;
use RuntimeException;
use Swoole\Atomic;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * 分组连接池
 */
class Group
{
    /**
     * 通道列表
     * 一个机器配置对应一个通道
     */
    protected array $channels = [];

    /**
     * 各通道已创建连接的数量
     */
    protected array $numbers = [];

    /**
     * 配置列表
     */
    protected array $configs = [];

    /**
     * 上下文标识
     */
    protected string $token;

    /**
     * 构造函数
     * @param $size     int     所有通道容量的总和
     * @param $configs  array   所有的机器配置
     * @param $token    string  上下文中连接的标识
     */
    public function __construct(int $size, array $configs, string $token)
    {
        // 计算每台机器的平均连接数
        $avg = floor($size / count($configs));
        // 计算余数
        $remainder = $size % count($configs);
        // 循环机器的配置
        foreach ($configs as $key => $config) {
            // 计算该配置的通道容量
            $poolSize = array_key_first($configs) == $key ? $avg + $remainder : $avg;
            // 实例化通道
            $this->channels[$key] = new Channel((int) $poolSize);
            // 实例化计数
            $this->numbers[$key] = new Atomic();
        }
        // 保存配置
        $this->configs = $configs;
        // 保存标识
        $this->token = $token;
    }

    /**
     * 获取上下文标识
     */
    public function token() : string
    {
        return sprintf('Pool:%s:Connection', $this->token);
    }

    /**
     * 取出连接
     */
    public function get($key = null)
    {
        // 存在连接
        if (isset(Coroutine::getContext()[$this->token()])) {
            return Coroutine::getContext()[$this->token()];
        }
        // Key不存在
        if (is_null($key) || !isset($this->channels[$key])) {
            $key = null;
            foreach ($this->channels as $k => $channel) {
                if (!$channel->isEmpty()) {
                    $key = $k;
                    break;
                }
            }
            if (is_null($key)) {
                $key = array_rand($this->channels);
            }
        }
        // 超时时间
        $timeout = $this->configs[$key]['timeout'] ?? 2;
        // 空的通道 并且还没创建满
        if ($this->channels[$key]->isEmpty() && $this->numbers[$key]->get() < $this->channels[$key]->capacity) {
            // 去创建连接
            $this->make($key);
        }
        // 得到连接
        Coroutine::getContext()[$this->token()] = $conn = $this->channels[$key]->pop($timeout);
        if (false === $conn) {
            throw new RuntimeException('get connection timeout');
        }
        // 用完得还
        Coroutine::defer(function() use($key, $conn){
            if (isset(Coroutine::getContext()[$this->token()])) {
                unset(Coroutine::getContext()[$this->token()]);
            }
            $this->put($key, $conn);
        });
        // 返回连接
        return $conn;
    }

    /**
     * 归还连接
     */
    public function put($key, $conn)
    {
        // 释放连接
        $conn->release();
        // 将连接存放到通道
        $bool = $this->channels[$key]->push($conn, 2);
        if (!$bool) {
            throw new RuntimeException('push connection timeout');
        }
    }

    /**
     * 填充连接
     */
    public function fill(int $size = null) : void
    {
        $size = $size ?? $this->size;
        foreach ($this->channels as $key => $channel) {
            $temp = $channel->capacity - $this->numbers[$key]->get();
            if ($temp > 0 && $size > 0) {
                for ($i = 0;$i < $temp && $size > 0; $i++) {
                    $size--;
                    $channel->push($this->make($key));
                }
            }
        }
    }

    /**
     * 关闭连接
     */
    public function close() : void
    {
        foreach ($this->channels as $key => $channel) {
            $this->channels[$key]->close();
            $this->channels[$key] = null;
        }
        $this->channels = [];
        foreach ($this->numbers as $key => $atomic) {
            $this->numbers[$key]->set(0);
            $this->numbers[$key] = null;
        }
        $this->numbers = [];
    }

    /**
     * 创建连接
     */
    private function make($key)
    {
        // 该通道创建的连接数量增加
        $number = $this->numbers[$key]->add();
        try {
            // 取出配置
            $config = $this->configs[$key];
            // 具体驱动
            $handle = $config['handle'];
            // 创建对应的连接驱动
            $conn = new $handle($config);
        } catch (Throwable $th) {
            // 该通道创建的连接数量减少
            $this->numbers[$key]->sub();
            // 继续抛出异常
            throw $th;
        }
        // 归还连接
        $this->put($key, $conn);
    }
}