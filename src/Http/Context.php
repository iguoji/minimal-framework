<?php
declare(strict_types=1);

namespace Minimal\Http;

use Swoole\Coroutine;
use Minimal\Support\Collection;

/**
 * 协程上下文
 */
class Context extends Collection
{
    /**
     * 解析Key
     */
    public function parseKey(string|int $key) : string
    {
        return 'coroutine:' . $this->id() . '.' . $key;
    }

    /**
     * 获取编号
     */
    public function id(int|bool $cid = null) : int
    {
        // 当前编号
        $cid = $cid ?? Coroutine::getCid();
        if (-1 === $cid || false === $cid) {
            throw new \Swoole\Error('API must be called in the coroutine');
        }
        // 父类编号
        $pcid = Coroutine::getPcid($cid);
        if (-1 === $pcid || false === $pcid) {
            return $cid;
        }
        // 继续寻找
        return $this->id($pcid);
    }

    /**
     * 删除所有数据
     */
    public function clear() : void
    {
        unset($this->dataset['coroutine:' . $this->id()]);
    }
}