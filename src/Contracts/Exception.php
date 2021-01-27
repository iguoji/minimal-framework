<?php
declare(strict_types=1);

namespace Minimal\Contracts;

/**
 * 异常类
 */
abstract class Exception extends \Exception
{
    /**
     * 异常数据
     */
    protected array $data;

    /**
     * 构造函数
     */
    public function __construct(string $message, int $code = 0, array $data = [])
    {
        $this->data = $data;
        parent::__construct($message, $code);
    }

    /**
     * 设置数据
     */
    public function setData(array $data) : static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取数据
     */
    public function getData() : array
    {
        return $this->data;
    }
}