<?php
declare(strict_types=1);

namespace Minimal\Pool;

use Minimal\Support\Arr;

/**
 * 集群连接池
 */
class Cluster
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
    public function __construct(array $configs)
    {
        // 默认配置
        $configs = Arr::array_merge_recursive_distinct($this->getDefaultConfigStruct(), $configs);
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
     * 获取链接
     */
    public function connection($key = null, string $group = null)
    {
        if (is_null($group) || !isset($this->pools[$group])) {
            $group = array_key_first($this->pools);
        }
        return $this->pools[$group]->get($key);
    }

    /**
     * 获取默认配置结构
     */
    public function getDefaultConfigStruct() : array
    {
        return [
            'pool'          =>  [
                'master'        =>  0,
                'slave'         =>  0,
            ],
            'default'       =>  [
                'handle'        =>  '',
                'host'          =>  '127.0.0.1',
                'port'          =>  0,
                'dbname'        =>  0,
                'username'      =>  '',
                'password'      =>  '',
                'timeout'       =>  0,
                'prefix'        =>  ''
            ],
            'cluster'       =>  [
                'master'        =>  [],
                'slave'         =>  [],
            ],
        ];
    }

    /**
     * 函数调用
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}