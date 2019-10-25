<?php


namespace Infrastructure\Repository\Repository;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\Result;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use Infrastructure\Repository\Utility\PreProcess;



class AbstractRepository
{
    private $lastQueryResult;
    private $lastQuery;
    /* 快速支持连贯操作 */
    private $fields = "*";
    private $limit  = NULL;
    private $withTotalCount = FALSE;
    private $order  = NULL;
    private $where  = [];
    private $join   = NULL;
    private $group  = NULL;

    /** @var string 表名 */
    protected $tableName = '';
    protected $primaryKey = NULL;
    protected $alias;


    /**
     * 当前连接驱动类的名称
     * 继承后可以覆盖该成员以指定默认的驱动类
     * @var string
     */
    protected $connectionName = 'default';
    /*
     * 临时设定的链接
     */
    private $tempConnectionName = null;

    /* 回调事件 */
    private $onQuery;

    /**
     * @return null
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function alias(string $aliasName){
        $this->alias = $aliasName;
    }

    public function getAlias(){
        if(!empty($this->alias)){
            return $this->tableName . ' AS '.$this->alias;
        }
        else{
            return $this->tableName;
        }
    }


        /*  ==============    回调事件    ==================   */
    public function onQuery(callable $call)
    {
        $this->onQuery = $call;
        return $this;
    }
    /*  ==============    快速支持连贯操作    ==================   */
    /**
     * @param mixed ...$args
     * @return AbstractRepository
     */
    public function order(...$args)
    {
        $this->order = $args;
        return $this;
    }
    /**
     * @param int $one
     * @param int|null $two
     * @return $this
     */
    public function limit(int $one, ?int $two = null)
    {
        if ($two !== null) {
            $this->limit = [$one, $two];
        } else {
            $this->limit = $one;
        }
        return $this;
    }
    /**
     * @param $fields
     * @return $this
     */
    public function field($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->fields = $fields;
        return $this;
    }
    /**
     * @return $this
     */
    public function withTotalCount()
    {
        $this->withTotalCount = true;
        return $this;
    }
    /**
     * @param $where
     * @return $this
     */
    public function where($where)
    {
        $this->where[] = $where;
        return $this;
    }
    /**
     * @param string $group
     * @return $this
     */
    public function group(string $group)
    {
        $this->group = $group;
        return $this;
    }
    /**
     * @param $joinTable
     * @param $joinCondition
     * @param string $joinType
     * @return $this
     */
    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $this->join[] = [$joinTable, $joinCondition, $joinType];
        return $this;
    }

    public function max($field)
    {
        return $this->queryPolymerization('max', $field);
    }

    public function min($field)
    {
        return $this->queryPolymerization('min', $field);
    }

    public function count($field = null)
    {
        return $this->queryPolymerization('count', $field);
    }

    public function avg($field)
    {
        return $this->queryPolymerization('avg', $field);
    }

    public function sum($field)
    {
        return $this->queryPolymerization('sum', $field);
    }

    /*  ==============    Builder 和 Result    ==================   */
    public function lastQueryResult(): ?Result
    {
        return $this->lastQueryResult;
    }
    public function lastQuery(): ?QueryBuilder
    {
        return $this->lastQuery;
    }

    function __construct()
    {
    }

    function connection(string $name, bool $isTemp = false): AbstractRepository
    {
        if ($isTemp) {
            $this->tempConnectionName = $name;
        } else {
            $this->connectionName = $name;
        }
        return $this;
    }

    /**
     * @param null $where
     * @return int|null
     * @throws Exception
     * @throws \Throwable
     */
    public function destroy($where = null): ?int
    {
        $builder = new QueryBuilder();

        PreProcess::mappingWhere($builder, $where, $this);
        $this->preHandleQueryBuilder($builder);
        $builder->delete($this->getAlias(), $this->limit);
        $this->query($builder);
        return $this->lastQueryResult()->getAffectedRows();
    }

    /**
     * 保存 插入
     * @param  $rawArray
     * @throws Exception
     * @throws \Throwable
     * @return bool|int
     */
    public function save(array $rawArray)
    {
        $builder = new QueryBuilder();
        if (empty($this->primaryKey)) {
            throw new Exception('save() needs primaryKey for model ' . static::class);
        }
        $builder->insert($this->getAlias(), $rawArray);
        $this->preHandleQueryBuilder($builder);
        $this->query($builder);
        if ($this->lastQueryResult()->getResult() === false) {
            return false;
        }
        if ($this->lastQueryResult()->getLastInsertId()) {
            return $this->lastQueryResult()->getLastInsertId();
        }
    }

    /**
     * 获取数据
     * @param null $where
     * @return array|null
     * @throws Exception
     * @throws \Throwable
     */
    public function get($where = null)
    {
        $modelInstance = new static;
        $builder = new QueryBuilder;
        $builder = PreProcess::mappingWhere($builder, $where, $modelInstance);
        $this->preHandleQueryBuilder($builder);
        $builder->getOne($this->getAlias(), $this->fields);
        $res = $this->query($builder);
        if (empty($res)) {
            return null;
        }
        $res = PreProcess::convertHump(($res[0]));
        $this->lastQuery = $this->lastQuery();
        return $res;
    }

    /**
     * 批量查询
     * @param null $where
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function all($where = null): array
    {
        $builder = new QueryBuilder;
        $builder = PreProcess::mappingWhere($builder, $where, $this);
        $this->preHandleQueryBuilder($builder);
        $builder->get($this->getAlias(), $this->limit, $this->fields);
        $results = $this->query($builder);
        $resultSet = [];
        if (is_array($results)) {
            $resultSet = PreProcess::convertHump($results);
        }
        return $resultSet;
    }

    /**
     * 更新
     * @param array $data
     * @param null  $where
     * @return bool
     * @throws Exception
     * @throws \Throwable
     */
    public function update(array $data = [], $where = null)
    {
        if (empty($data)) {
            return true;
        }
        $builder = new QueryBuilder();
        if ($where) {
            PreProcess::mappingWhere($builder, $where, $this);
        } else {
            if (isset($this->data[$this->primaryKey])) {
                $pkVal = $this->data[$this->primaryKey];
                $builder->where($this->primaryKey, $pkVal);
            } else {
                throw new Exception("update error,pkValue is require");
            }
        }
        $this->preHandleQueryBuilder($builder);
        $builder->update($this->getAlias(), $data);
        $results = $this->query($builder);
        return $results ? true : false;
    }

    protected function reset()
    {
        $this->tempConnectionName = null;

        $this->fields = "*";
        $this->limit  = NULL;
        $this->withTotalCount = FALSE;
        $this->order  = NULL;
        $this->where  = [];
        $this->join   = NULL;
        $this->group  = NULL;
        $this->alias  = NULL;
    }

    protected function query(QueryBuilder $builder, bool $raw = false)
    {
        $start = microtime(true);
        $this->lastQuery = clone $builder;
        if ($this->tempConnectionName) {
            $connectionName = $this->tempConnectionName;
        } else {
            $connectionName = $this->connectionName;
        }
        try {
            $ret = null;
            $ret = DbManager::getInstance()->query($builder, $raw, $connectionName);
            $builder->reset();
            $this->lastQueryResult = $ret;
            return $ret->getResult();
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $this->reset();
            if ($this->onQuery) {
                $temp = clone $builder;
                call_user_func($this->onQuery, $ret, $temp, $start);
            }
        }
    }

    /**
     * 连贯操作预处理
     * @param QueryBuilder $builder
     * @throws Exception
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    protected function preHandleQueryBuilder(QueryBuilder $builder)
    {
        // 快速连贯操作
        if ($this->withTotalCount) {
            $builder->withTotalCount();
        }
        if ($this->order) {
            $builder->orderBy(...$this->order);
        }
        if ($this->where) {
            $whereModel = new static();
            foreach ($this->where as $whereOne){
                $builder = PreProcess::mappingWhere($builder, $whereOne, $whereModel);
            }
        }
        if($this->group){
            $builder->groupBy($this->group);
        }
        if($this->join){
            foreach ($this->join as $joinOne) {
                $builder->join($joinOne[0], $joinOne[1], $joinOne[2]);
            }
        }
    }

    /**
     * @param $type
     * @param null $field
     * @return mixed|null
     * @throws Exception
     * @throws \Throwable
     */
    private function queryPolymerization($type, $field = null)
    {
        if ($field === null){
            $field = $this->primaryKey;
        }
        $fields = "$type(`{$field}`)";
        $this->fields = $fields;
        $this->limit = 1;
        $res = $this->all(null);

        if (!empty($res[0][$fields])){
            return $res[0][$fields];
        }
        return null;
    }

    /**
     * 批量查询 不映射对象  返回数组
     * @param null $where
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    public function select($where = null):array
    {
        return $this->all($where);
    }



    /**
     * 读写分离
     * @param string $connectionName
     * @return $this
     */
    public function separate(string $connectionName = 'write'){

        if(DbManager::getInstance()->getConnection($connectionName)) {
            $this->connection($connectionName, true);
        }
        return $this;
    }
}