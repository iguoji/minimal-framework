<?php
declare(strict_types=1);

namespace Minimal\Events\Database;

use PDO;
use Swoole\Coroutine;
use Minimal\Facades\Db;
use Minimal\Container\Container;
use Minimal\Annotations\Listener;
use Minimal\Contracts\Listener as ListenerInterface;

/**
 * 数据库 - 结构事件
 */
#[Listener]
class OnStruct implements ListenerInterface
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
        return ['Database:OnStruct'];
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
        // 基础目录
        $context = $arguments['context'];
        unset($arguments['context']);
        $basePath = $context['basePath'] . DIRECTORY_SEPARATOR;

        // 保存地址
        $file = $arguments['file'] ?? $basePath . 'config' . DIRECTORY_SEPARATOR . 'table.php';
        if (empty($file)) {
            echo 'Tips: php minimal database:struct -file /path/to/path/table.php', PHP_EOL;
            return true;
        }

        // 最终结构
        $structs = [];

        // 查询所有表
        $tables = Db::query('SHOW TABLE STATUS');
        // 循环所有表
        foreach ($tables as $table) {
            // 表名
            $tableName = $table['Name'];
            // 表备注
            $tableComment = $table['Comment'];
            // 查询所有字段
            $fields = Db::query("SHOW FULL FIELDS FROM `$tableName`");

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
        // $oldConfig = $this->container->config->get(basename($file, '.php'), []);
        // $structs = array_merge($structs, is_array($oldConfig) ? $oldConfig : []);

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
        echo 'success -file=' . $file, PHP_EOL;

        // 返回结果
        return true;
    }
}