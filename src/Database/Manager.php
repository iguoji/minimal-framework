<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use Minimal\Config;
use Minimal\Pool\Group;
use Minimal\Support\Arr;
use Minimal\Annotations\Inject;
use Minimal\Database\Mysql\Connection;

/**
 * 数据库
 */
#[Inject('', 'database')]
class Manager
{
    /**
     * 配置对象
     */
    protected array $configs;

    /**
     * 连接池标识
     */
    protected string $token = 'Default';

    /**
     * 连接池列表
     */
    protected array $pools;

    /**
     * 构造函数
     */
    public function __construct(Config $config)
    {
        // 配置信息
        $configs = $config->get('db', []);
        $configs = Arr::array_merge_recursive_distinct($this->getDefaultConfigStruct(), $configs);
        // 默认配置
        $defaultConfig = $configs['default'];
        // 循环分组
        foreach ($configs['cluster'] as $groupName => &$groupConfigs) {
            // 分组连接总数
            $poolSize = $configs['pool'][$groupName] ?? 0;
            if ($poolSize > 0) {
                // 合并默认配置
                $groupConfigs = array_map(fn($config) => array_merge($defaultConfig, $config), $groupConfigs ?: [$defaultConfig]);
                // 保存连接池
                $this->pools[$groupName] = new Group($poolSize, $groupConfigs, $this->token);
            }
        }
        // 保存配置
        $this->configs = $configs;
    }

    /**
     * 获取默认配置结构
     */
    public function getDefaultConfigStruct() : array
    {
        return [
            'pool'          =>  [
                'master'        =>  150,
                'slave'         =>  0,
            ],
            'default'       =>  [
                'handle'        =>  Connection::class,
                'host'          =>  '127.0.0.1',
                'port'          =>  3306,
                'dbname'        =>  '',
                'username'      =>  '',
                'password'      =>  '',
                'charset'       =>  'utf8mb4',
                'collation'     =>  'utf8mb4_unicode_ci',
                'timeout'       =>  2,
            ],
            'cluster'       =>  [
                'master'        =>  [],
                'slave'         =>  [],
            ],
        ];
    }

    /**
     * 获取连接
     */
    public function connection(string $key = null, string $group = 'slave')
    {
        if (is_null($group) || !isset($this->pools[$group])) {
            $group = array_key_first($this->pools);
        }
        if (is_null($group)) {
            throw new RuntimeException('no database config');
        }
        return $this->pools[$group]->get($key);
    }

    /**
     * 查询数据
     */
    public function query(string $sql, array $data = []) : array
    {
        $connection = $this->inTransaction() ? $this->connection(null, 'master') : $this->connection(null, 'slave');
        $statement = $connection->prepare($sql);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute($data);
        return $statement->fetchAll();
    }

    /**
     * 获取一行
     */
    public function first(string $sql, array $data = []) : mixed
    {
        $connection = $this->inTransaction() ? $this->connection(null, 'master') : $this->connection(null, 'slave');
        $statement = $connection->prepare($sql);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute($data);
        return $statement->fetch();
    }

    /**
     * 查询数值
     */
    public function number(string $sql, array $data = []) : mixed
    {
        $connection = $this->inTransaction() ? $this->connection(null, 'master') : $this->connection(null, 'slave');
        $statement = $connection->prepare($sql);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $statement->execute($data);
        return $statement->fetchColumn();
    }

    /**
     * 操作数据
     */
    public function execute($sql) : int
    {
        return $this->exec($sql);
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {
        return $this->connection(null, 'master')->$method(...$arguments);
    }
}