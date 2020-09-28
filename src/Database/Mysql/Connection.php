<?php
declare(strict_types=1);

namespace Minimal\Database\Mysql;

use PDO;
use PDOException;
use Minimal\Contracts\Connection as ConnectionInterface;

/**
 * Mysql连接
 */
class Connection implements ConnectionInterface
{
    /**
     * 驱动
     */
    protected PDO $handle;

    /**
     * 配置
     */
    protected array $config;

    /**
     * 重连错误码
     */
    const IO_ERRORS = [
        1927, // Connection was killed
        2002, // MYSQLND_CR_CONNECTION_ERROR
        2006, // MYSQLND_CR_SERVER_GONE_ERROR
        2013, // MYSQLND_CR_SERVER_LOST
    ];

    /**
     * 构造函数
     */
    public function __construct(array $config)
    {
        // 默认配置
        $this->config = array_merge([
            'host'          =>  '127.0.0.1',
            'port'          =>  3306,
            'dbname'        =>  '',
            'username'      =>  '',
            'password'      =>  '',
            'charset'       =>  'utf8mb4',
            'collation'     =>  'utf8mb4_unicode_ci',
            'timeout'       =>  1,
        ], $config);
        // 连接数据库
        $this->connect();
    }

    /**
     * 连接驱动
     */
    public function connect(bool $reconnect = true)
    {
        try {
            // 驱动选项
            $options = [];
            if (isset($this->config['timeout'])) {
                $options[PDO::ATTR_TIMEOUT] = (int) $this->config['timeout'];
            }
            // 创建驱动
            $this->handle = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s'
                    , $this->config['host']
                    , (int) $this->config['port']
                    , $this->config['dbname']
                    , $this->config['charset']
                )
                , $this->config['username']
                , $this->config['password']
                , $options
            );
            // 错误模式：通过错误代码得到问题，而非抛出异常
            $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            // 返回驱动
            return $this->handle;
        } catch (Throwable $th) {
            // 尝试重连一次
            if ($reconnect) {
                return $this->connect(false);
            }
            throw new PDOException($th->getMessage());
        }
    }

    /**
     * 释放连接
     */
    public function release()
    {
        // 如果还在事务中，得处理，不然下个请求无法开启事务
        // 这里选择回滚
        return $this->rollBack();
    }
}