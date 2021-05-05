<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use PDO;
use Throwable;
use PDOStatement;
use PDOException;
use StdClass;
use Minimal\Application;
use Minimal\Support\Str;

/**
 * 数据库
 */
class Database
{
    /**
     * PDO句柄
     */
    protected PDO $handle;

    /**
     * 最后的语句
     */
    protected string $sql;

    /**
     * 数据配置
     */
    protected array $config;

    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 参数名称
     */
    protected array $names = [];

    /**
     * 参数的值
     */
    protected array $values = [];

    /**
     * 预定义选项
     */
    protected array $options = [];

    /**
     * 预定义属性
     */
    protected array $attributes = [
        /**
         * 设置错误模式为抛出异常
         */
        PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,

        /**
         * 启用或禁用预处理语句的模拟。
         * 有些驱动不支持或有限度地支持本地预处理。
         * 使用此设置强制PDO总是模拟预处理语句（如果为 true ），或试着使用本地预处理语句（如果为 false）。
         * 如果驱动不能成功预处理当前查询，它将总是回到模拟预处理语句上。
         */
        PDO::ATTR_EMULATE_PREPARES  =>  false,

        /**
         * 提取的时候将数值转换为字符串
         * 只有同时和 PDO::ATTR_EMULATE_PREPARES 属性保持为 false 才有效果
         */
        PDO::ATTR_STRINGIFY_FETCHES =>  false,
    ];

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {
        // 读取配置
        $this->token = 'd'.mt_rand(1000, 9999);
        $this->config = $app->config->get('db', []);
    }





    /**
     * 连接驱动
     */
    public function connect(int $recount = 1) : PDO
    {
        try {
            $handle = new PDO($this->getDsn(), $this->config['username'], $this->config['password'], $this->getOptions());
            foreach ($this->getAttributes() as $key => $value) {
                $handle->setAttribute($key, $value);
            }
            $handle->token = 'p'.mt_rand(1000, 9999);
        } catch (PDOException $th) {
            $this->app->log->error($th->getMessage(), [
                'File'  =>  $th->getFile(),
                'Line'  =>  $th->getLine(),
            ]);
            if ($recount > 0) {
                return $this->connect($recount - 1);
            }
        }
        // $that = $this;
        // Swoole\Coroutine::defer(function()use($that){
        //     $that->release();
        // });
        return $handle;
    }

    /**
     * 当前连接
     */
    public function connection() : PDO
    {
        if ($this->app->context->has('database:handle')) {
            return $this->app->context->get('database:handle');
        }

        $this->app->context->set('database:handle', $handle = $this->connect());
        return $handle;
    }

    /**
     * 释放驱动
     */
    public function release() : void
    {
        unset($this->handle);
    }

    /**
     * 获取DSN
     */
    private function getDsn() : string
    {
        return isset($this->config['unix_socket'])
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $this->config['unix_socket'], $this->config['dbname'], $this->config['charset'] ?? 'utf8')
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $this->config['host'], $this->config['port'], $this->config['dbname'], $this->config['charset'] ?? 'utf8');
    }

    /**
     * 获取PDO选项
     */
    private function getOptions() : array
    {
        $userOptions = $this->config['options'] ?? [];
        foreach ($userOptions as $key => $value) {
            $this->options[$key] = $value;
        }
        return $this->options;
    }

    /**
     * 获取PDO属性
     */
    private function getAttributes() : array
    {
        $userAttributes = $this->config['attributes'] ?? [];
        foreach ($userAttributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this->attributes;
    }





    /**
     * 开启事务
     */
    public function beginTransaction() : bool
    {
        $level = $this->app->context->inc('database:transaction:level');
        $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('1', 30)));
        if (1 === (int) $level) {
            $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('2', 30)));
            $bool = $this->__call('beginTransaction', []);
            $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('3', 30)));
            return $bool;
        }
        $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('4', 30)));

        $this->__call('exec', ['SAVEPOINT TRANS' . $level]);
        return true;
    }

    /**
     * 提交事务
     */
    public function commit() : bool
    {
        $level = $this->app->context->dec('database:transaction:level');
        $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('5', 30)));
        if (0 === (int) $level) {
            return $this->__call('commit', []);
        }

        return true;
    }

    /**
     * 回滚事务
     */
    public function rollBack() : bool
    {
        $level = $this->app->context->get('database:transaction:level');
        $this->app->log->info(sprintf('[%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, str_repeat('6', 30)));
        if (1 === (int) $level) {
            $bool = $this->__call('rollBack', []);
        } else {
            $this->__call('exec', ['ROLLBACK TO SAVEPOINT TRANS' . $level]);
        }

        $level = max(0, $level - 1);
        $this->app->context->set('database:transaction:level', $level);

        return isset($bool) ? $bool : true;
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool
    {
        return $this->app->context->has('database:transaction:level')
            && $this->app->context->get('database:transaction:level') >= 1;
    }





    /**
     * 原始语句
     */
    public function raw(string $sql) : StdClass
    {
        $raw = new StdClass();
        $raw->sql = $sql;
        return $raw;
    }

    /**
     * 按表查询
     */
    public function table(string $table, string $as = null) : static
    {
        return $this->from($table, $as);
    }

    /**
     * 主表别名
     */
    public function from(string $table, string $as = null) : static
    {
        $this->reset();

        $this->bindings['from'] = is_null($as) ? $this->backquote($table) : $this->backquote($table) . ' AS ' . $this->backquote($as);

        return $this;
    }

    /**
     * 过滤重复
     */
    public function distinct() : static
    {
        $this->bindings['distinct'] = 'DISTINCT';

        return $this;
    }

    /**
     * 字段
     */
    public function field(StdClass|string|array ...$columns) : static
    {
        $this->bindings['field'] = implode(', ', array_map(function($column){
            if ($column instanceof StdClass) {
                return $column->sql;
            } else {
                $rel = $this->backquote($column);
                return is_array($rel) ? implode(' AS ', $rel) : $rel;
            }
        }, $columns));

        return $this;
    }

    /**
     * 表连接
     */
    public function join(string $table, string $as, string $column, string $value, string $type = 'INNER JOIN') : static
    {
        $this->bindings['join'][] = implode(' ', [
            $type,
            $this->backquote($table), 'AS', $this->backquote($as),
            'ON',
            $this->backquote($column), '=', $this->backquote($value)
        ]);

        return $this;
    }

    /**
     * 表连接 - 左
     */
    public function leftJoin(string $table, string $as, string $column, string $value) : static
    {
        return $this->join($table, $as, $column, $value, 'LEFT JOIN');
    }

    /**
     * 表连接 - 右
     */
    public function rightJoin(string $table, string $as, string $column, string $value) : static
    {
        return $this->join($table, $as, $column, $value, 'RIGHT JOIN');
    }

    /**
     * 表连接 - 交叉
     */
    public function crossJoin(string $table, string $as, string $column, string $value) : static
    {
        return $this->join($table, $as, $column, $value, 'CROSS JOIN');
    }

    /**
     * 条件
     */
    public function where(callable|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND', string $location = 'where') : static
    {
        $data = [];
        if (is_callable($column)) {
            $query = clone $this;
            $data = [$logic, $column($query)->getBinding($location)];
            $this->names = $query->getNames();
            $this->values = $query->getValues();
        } else {
            $express = $this->backquote($column) . ' ';

            if (is_null($value)) {
                if (is_null($operator)) {
                    $express .= 'IS NULL';
                } else if (in_array($operator, ['!=', '<>'])) {
                    $express .= 'IS NOT NULL';
                } else {
                    $value = $operator;
                    $operator = '=';
                }
            }

            if (!is_null($value)) {
                $express .= $operator . ' ' . $this->mark($column, $value);
            }

            $data = [$logic, $express];
        }
        $this->bindings[$location][] = $data;

        return $this;
    }

    /**
     * 条件 - 或
     */
    public function orWhere(callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * 分组
     */
    public function groupBy(string ...$groups) : static
    {
        $this->bindings['groupBy'] = 'GROUP BY ' . implode(', ', array_map(fn($s) => $this->backquote($s), $groups));

        return $this;
    }

    /**
     * 条件 - 分组后
     */
    public function having(callable|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        return $this->where($column, $operator, $value, $logic, 'having');
    }

    /**
     * 条件 - 分组后 - 或
     */
    public function orHaving(callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * 排序
     */
    public function orderBy(string $column, string $direction = 'ASC') : static
    {
        $this->bindings['orderBy'][] = $this->backquote($column) . ' ' . $direction;

        return $this;
    }

    /**
     * 排序 - 倒序
     */
    public function orderByDesc(string $column) : static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * 分页
     */
    public function page(int $no, int $size) : static
    {
        return $this->limit(($no - 1) * $size, $size);
    }

    /**
     * 限量偏移
     */
    public function limit(int $offset, int $count = null) : static
    {
        $this->bindings['limit'] = 'LIMIT ' . $offset . (is_null($count) ? '' : ', ' . $count);

        return $this;
    }

    /**
     * 表联合
     */
    public function union(callable $callback, bool $all = false) : static
    {
        $query = $callback(clone $this);
        $query->removeBinding('orderBy', 'limit');

        $this->bindings['union'] = 'UNION' . ($all ? ' ALL ' : ' DISTINCT ') . $query->buildSelect();
        $this->names = $query->getNames();
        $this->values = $query->getValues();

        return $this;
    }





    /**
     * 构建查询语句
     */
    private function buildCondition(string $type, array $conditions = []) : string
    {
        $sql = '';

        $conditions = 1 === func_num_args() ? $this->getBinding($type) : $conditions;
        foreach ($conditions as $condition) {
            [$logic, $express] = $condition;

            if ('' !== $sql) {
                $sql .= ' ' . $logic . ' ';
            }

            if (is_array($express)) {
                $sql .= '(' . $this->buildCondition($type, $express) . ')';
            } else {
                $sql .= $express;
            }
        }

        return $sql;
    }

    /**
     * 构建查询语句
     */
    public function buildSelect() : string
    {
        return rtrim(implode(' ', [
            'SELECT',
            $this->getBinding('distinct'),
            $this->getBinding('field') ?? '*',
            is_null($this->getBinding('from')) ? '' : 'FROM ' . $this->getBinding('from'),
            is_null($this->getBinding('join')) ? '' : implode(' ', $this->getBinding('join')),
            is_null($this->getBinding('where')) ? '' : 'WHERE ' . $this->buildCondition('where'),
            $this->getBinding('groupBy'),
            is_null($this->getBinding('having')) ? '' : 'HAVING ' . $this->buildCondition('having'),
            $this->getBinding('union'),
            is_null($this->getBinding('orderBy')) ? '' : 'ORDER BY ' . implode(', ', $this->getBinding('orderBy')),
            $this->getBinding('limit'),
        ]));
    }

    /**
     * 构建插入语句
     */
    public function buildInsert(array $data) : string
    {
        if (!is_array(reset($data))) {
            $data = [$data];
        }

        $fields = $this->backquote(array_keys($data[0]));
        $values = [];
        foreach ($data as $key => $item) {
            foreach ($item as $k => $v) {
                $values[$key][] = $this->mark($k, $v);
            }
            $values[$key] = '(' . implode(', ', $values[$key]) . ')';
        }

        return implode(' ', [
            'INSERT INTO',
            $this->getBinding('from'),
            '(' . implode(', ', $fields) . ')',
            'VALUES',
            implode(', ', $values),
            is_null($this->getBinding('where')) ? '' : 'WHERE ' . $this->buildCondition('where'),
        ]);
    }

    /**
     * 构建修改语句
     */
    public function buildUpdate(array $data) : string
    {
        $setdata = [];
        foreach ($data as $column => $value) {
            if ($value instanceof StdClass) {
                $mark = $value->sql;
            } else {
                $mark = $this->mark($column, $value);
            }
            $setdata[] = $this->backquote($column) . ' = ' . $mark;
        }

        return implode(' ', [
            'UPDATE',
            $this->getBinding('from'),
            'SET',
            implode(', ', $setdata),
            is_null($this->getBinding('where')) ? '' : 'WHERE ' . $this->buildCondition('where'),
        ]);
    }

    /**
     * 构建删除语句
     */
    public function buildDelete() : string
    {
        return implode(' ', [
            'DELETE FROM',
            $this->getBinding('from'),
            is_null($this->getBinding('where')) ? '' : 'WHERE ' . $this->buildCondition('where'),
        ]);
    }






    /**
     * 聚合函数
     */
    public function aggregate(string $func, array $columns = ['*']) : mixed
    {
        return $this->value(
            $this->raw(
                strtoupper($func)
                . '('
                . implode(', ', static::backquote($columns))
                . ')'
            )
        );
    }

    /**
     * 聚合 - 统计
     */
    public function count(StdClass|string $column = '*') : int
    {
        return (int) $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 最小值
     */
    public function min(StdClass|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 最大值
     */
    public function max(StdClass|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 总和
     */
    public function sum(StdClass|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 平均值
     */
    public function avg(StdClass|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 递增
     */
    public function inc(StdClass|string $column, float|int $step = 1, array $extra = []) : int
    {
        return $this->update([
            $column     =>  $this->raw($this->backquote($column) . ' + ' . $step)
        ]);
    }

    /**
     * 递减
     */
    public function dec(StdClass|string $column, float|int $step = 1, array $extra = []) : int
    {
        return $this->update([
            $column     =>  $this->raw($this->backquote($column) . ' - ' . $step)
        ]);
    }

    /**
     * 查询数据 - 所有
     */
    public function all(StdClass|string|array ...$columns) : array
    {
        if (!isset($this->bindings['field']) || func_num_args()) {
            $this->field(...($columns ?: ['*']));
        }
        return $this->query($this->buildSelect(), $this->getValues())->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 查询数据 - 所有 - 别名
     */
    public function select(StdClass|string|array ...$columns) : array
    {
        return func_num_args() ? $this->all(...$columns) : $this->all();
    }

    /**
     * 查询数据 - 第一行
     */
    public function first(StdClass|string|array ...$columns) : array
    {
        if (!isset($this->bindings['field']) || func_num_args()) {
            $this->field(...($columns ?: ['*']));
        }
        return $this->query($this->buildSelect(), $this->getValues())->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 查询数据 - 第一列
     */
    public function column(StdClass|string $column) : array
    {
        return $this->field($column)->query($this->buildSelect(), $this->getValues())->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * 查询数据 - 单个值
     */
    public function value(StdClass|string $column) : mixed
    {
        return $this->field($column)->query($this->buildSelect(), $this->getValues())->fetchColumn();
    }

    /**
     * 插入数据
     */
    public function insert(array $data) : bool
    {
        return $this->execute($this->buildInsert($data), $this->getValues()) > 0;
    }

    /**
     * 修改数据
     */
    public function update(array $data) : int
    {
        return $this->execute($this->buildUpdate($data), $this->getValues());
    }

    /**
     * 删除数据
     */
    public function delete() : int
    {
        return $this->execute($this->buildDelete(), $this->getValues());
    }

    /**
     * 清空表
     */
    public function truncate() : bool
    {
        $this->execute('TRUNCATE TABLE ' . $this->getBinding('from'), []);

        return true;
    }

    /**
     * 分块处理
     */
    public function chunk(int $count, Closure $callback) : bool
    {
        $page = 1;

        do {
            $results = $this->page($page, $count)->all();

            $countResults = count($results);

            if ($countResults == 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);

            $page++;
        } while ($countResults == $count);

        return true;
    }










    /**
     * 执行查询
     */
    public function query(string $sql, array $parameters = []) : PDOStatement
    {
        $this->values = $parameters;
        return $this->__call('prepare', [$sql]);
    }

    /**
     * 执行语句
     */
    public function execute(string $sql, array $parameters = []) : int
    {
        $statement = $this->query($sql, $parameters);
        return $statement->rowCount();
    }





    /**
     * 获取最后的语句
     */
    public function lastSql() : string
    {
        return $this->sql ?? '';
    }

    /**
     * 获取最后的自增ID
     */
    public function lastInsertId(string $name = null) : string
    {
        return $this->__call('lastInsertId', [$name]);
    }

    /**
     * 转成Sql
     */
    public function toSql() : string
    {
        return 'SQL';
    }





    /**
     * 反引号
     */
    private function backquote(array|string $sql, string $symbol = '`', string $delimiter = '.', array $excepts = ['*']) : array|string
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $subsql) {
                $sql[$key] = $this->backquote($subsql, $symbol, $delimiter, $excepts);
            }
            return $sql;
        }
        return Str::map($sql, fn($s) => in_array($s, $excepts) ? $s : $symbol . $s . $symbol, $delimiter);
    }

    /**
     * 标记占位符
     */
    public function mark(string $column, mixed $value = null) : string
    {
        $mark = ':' . preg_replace("/[^\w]/", "_", $column);

        if (!isset($this->names[$mark])) {
            $this->names[$mark] = 0;
        }
        $this->names[$mark]++;

        $mark .= $this->names[$mark];

        if (2 === func_num_args()) {
            $this->values[$mark] = $value;
        }

        return $mark;
    }

    /**
     * 获取名称
     */
    public function getNames() : mixed
    {
        return $this->names;
    }

    /**
     * 获取数据
     */
    public function getBinding(string $key) : mixed
    {
        return $this->bindings[$key] ?? null;
    }

    /**
     * 删除数据
     */
    public function removeBinding(string ...$keys) : static
    {
        foreach ($keys as $key) {
            unset($this->bindings[$key]);
        }

        return $this;
    }

    /**
     * 获取参数
     */
    public function getValue(string $key) : mixed
    {
        return $this->values[$key] ?? null;
    }

    /**
     * 获取所有参数
     */
    public function getValues() : array
    {
        return $this->values;
    }

    /**
     * 清空参数
     */
    public function reset() : static
    {
        $this->bindings = [];
        $this->names = [];
        $this->values = [];

        return $this;
    }





    /**
     * 对象克隆
     */
    public function __clone()
    {
        $this->bindings = [];
    }

    /**
     * 未知方法
     */
    public function __call(string $method, array $arguments)
    {
        $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, __FUNCTION__, '', str_repeat('a', 30)));
        // 连接驱动
        $this->handle = $this->connection();
        // if (!isset($this->handle)) {
        //     $this->app->log->info('[' . \Swoole\Coroutine::getuid() . '] ' . $method . str_repeat('b', 30));
        //     $this->connect();
        //     $this->app->log->info('[' . \Swoole\Coroutine::getuid() . '] ' . $method . str_repeat('c', 30));
        // }

        // 尝试三次
        for ($i = 0;$i < 3;$i++) {
            try {
                $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('d', 30)));
                // 调用方法
                $result = $this->handle->$method(...$arguments);

                // 保存语句
                if ($result instanceof PDOStatement) {
                    $this->sql = $result->queryString;
                } else if ($method == 'exec') {
                    $this->sql = $arguments[0] ?? '';
                }

                // 主动执行
                if ($result instanceof PDOStatement) {
                    $bool = $result->execute($this->values);
                    if (false === $bool) {
                        throw new PDOException('database PDOStatement execute fail');
                    }
                }
                $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('e', 30)));
                // 执行成功
                break;
            } catch (Throwable $ex) {
                $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('f', 30)));
                // 错误重连
                if (
                    in_array($this->handle->errorInfo()[1], [2002, 2006, 2013]) ||
                    (isset($result) && !is_null($result) && in_array($result->errorInfo()[1], [2002, 2006, 2013]))
                ) {
                    $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('g', 30)));
                    $this->app->log->info('数据库重连：');
                    $this->connect();
                    $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('h', 30)));
                    continue;
                }
                // 记录错误
                $this->app->log->error($ex->getMessage(), [
                    'coroutine' =>  \Swoole\Coroutine::getuid(),
                    'token'     =>  $this->token,
                    'handle'     =>  $this->handle->token,
                    'code'      =>  $ex->getCode(),
                    'error1'    =>  $this->handle->errorInfo(),
                    'error2'    =>  isset($result) && !is_null($result) ? $result->errorInfo() : [],
                    'method'    =>  $method,
                    'arguments' =>  $arguments,
                    'parameters'=>  $this->values,
                ]);
                throw $ex;
            }
        }

        $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('i', 30)));

        // 清空参数
        $this->reset();

        $this->app->log->info(sprintf('[%s] [%s] [%s] [%s] %s', \Swoole\Coroutine::getuid(), $this->token, $method, $this->handle->token, str_repeat('j', 30)));

        // 返回结果
        return $result;
    }
}