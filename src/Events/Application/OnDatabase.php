<?php
declare(strict_types=1);

namespace Minimal\Events\Application;

use PDO;
use Minimal\Container\Container;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 应用程序 - 数据库事件
 */
#[Listener]
class OnDatabase implements ListenerInterface
{
    /**
     * 构造函数
     */
    public function __construct(protected Container $container)
    {}

    /**
     * 监听的事件列表
     */
    public function events() : array
    {
        return ['Application:OnDatabaseStruct'];
    }

    /**
     * 获取连接
     */
    public function connect(array $config, bool $reconnect = true) : PDO
    {
        try {
            // 创建驱动
            $handle = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s'
                    , $config['host']
                    , (int) $config['port']
                    , $config['dbname']
                    , $config['charset']
                )
                , $config['username']
                , $config['password']
                , $config['options'] ?? []
            );
            // 返回驱动
            return $handle;
        } catch (Throwable $th) {
            // 尝试重连一次
            if ($reconnect) {
                return $this->connect($config, false);
            }
            throw $th;
        }
    }

    /**
     * 解析类型
     */
    public function type(string $type) {
        if (false !== strpos($type, '(')) {
            $type = explode('(', $type)[0];
        }
        switch ($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
                return 'int';
                break;
            case 'float':
            case 'double':
            case 'decimal':
                return 'float';
                break;
            default:
                return 'string';
                break;
        }
    }

    /**
     * 处理过程
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 数据库结构
        if ($event == 'Application:OnDatabaseStruct') {
            // 保存地址
            $file = $arguments['file'] ?? null;
            if (empty($file)) {
                echo 'Tips: php minimal database:struct -file /path/to/path/table.php', PHP_EOL;
                return true;
            }

            // 获取配置
            $config = $this->container->config->get('db.default');
            // 获取连接
            $conn = $this->connect($config);

            // 最终结构
            $structs = [];

            // 查询所有表
            $tables = $conn->query('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC);
            // 循环所有表
            foreach ($tables as $table) {
                // 表名
                $tableName = $table['Name'];
                // 表备注
                $tableComment = $table['Comment'];
                // 查询所有字段
                $fields = $conn->query("SHOW FULL FIELDS FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);

                // 组织数据
                $structs[$tableName] = [
                    'name'      =>  $tableName,
                    'comment'   =>  $tableComment,
                    'fields'    =>  [],
                ];
                foreach ($fields as $field) {
                    $structs[$tableName]['fields'][$field['Field']] = [
                        'name'      =>  $field['Field'],
                        'type'      =>  $this->type($field['Type']),
                        'nullable'  =>  $field['Null'] === 'YES',
                        'default'   =>  $field['Default'],
                        'comment'   =>  $field['Comment'],
                    ];
                }
            }

            // 合并老的结构
            $oldConfig = $this->container->config->get(basename($file, '.php'), []);
            $structs = array_merge($structs, is_array($oldConfig) ? $oldConfig : []);

            // 保存结构
            $export = var_export($structs, true);
            $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
            $array = preg_split("/\r\n|\n|\r/", $export);
            $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
            $export = join(PHP_EOL, array_filter(["["] + $array));


            // 写入文件
            if (false === file_put_contents($file, "<?php\r\n\r\nreturn $export;")) {
                echo 'write file fail', PHP_EOL;
                return true;
            }

            // 返回结果
            echo 'success', PHP_EOL;
        }
        // 返回结果
        return true;
    }
}