<?php


namespace Infrastructure\Repository\Repository;


use EasySwoole\MysqliPool\Connection;
use EasySwoole\MysqliPool\Mysql;
use Infrastructure\Repository\Contracts\CriteriaInterface;
use Infrastructure\Repository\Contracts\RepositoryInterface;

class BaseRepository implements RepositoryInterface
{
    protected $table;

    /** @var Connection */
    protected $db;

    /**
     * @var array
     */
    protected $criteria = [];
    /**
     * BaseRepository constructor.
     * @throws \EasySwoole\Component\Pool\Exception\PoolEmpty
     * @throws \EasySwoole\Component\Pool\Exception\PoolException
     */
    public function __construct()
    {
        $this->db = Mysql::getInstance()->pool('mysql')::defer();
    }

    /**
     * @param string $column
     * @param string|null $key
     * @return array|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function lists(string $column,string $key = null)
    {
        $this->applyCriteria();
        $query = $this->db->get($this->table,null,"$column,$key");
        if(!empty($query)){
            foreach ($query as $value){
                $res[$value[$key]] = $value[$column];
            }
            return $res;
        }
        return [];
    }

    /**
     * @param string $column
     * @return array|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function pluck(string $column){
        $this->applyCriteria();
        return $this->db->getColumn($this->table,$column);
    }

    /**
     * @param string $columns
     * @return array|\Infrastructure\Pool\Mysql\MysqlObject|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function all(string $columns = '*'){
        $this->applyCriteria();
        $res = $this->db->where('logicid','294962')->get($this->table,null,$columns);
        return  $res;

    }

    /**
     * @param array|null $numRows
     * @param string $columns
     * @return array|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\Option
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function paginate(array $numRows = null, string $columns = '*'){
        $this->applyCriteria();
        $list = $this->db->withTotalCount()->get($this->table,$numRows,$columns) ?: [];
        $total = $this->db->getTotalCount() ?: 0;
        return[
            'list'  =>$list,
            'total' =>$total
        ];
    }

    /**
     * @param int $id
     * @param string $columns
     * @return \EasySwoole\Mysqli\Mysqli|mixed|null
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function find(int $id, string $columns = '*'){
        return $this->db->where('id',$id)->getOne($this->table,$columns);
    }

    /**
     * @param string $field
     * @param $value
     * @param string $columns
     * @return array|\EasySwoole\Mysqli\Mysqli|mixed|null
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function findByField(string $field, $value, string $columns = '*'){
        return $this->db->where($field,$value)->getOne($this->table,$columns);
    }


    /**
     * @param array $where
     * @param string $columns
     * @return MysqlObject|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function findWhere(array $where, string $columns = '*'){
        $this->applyConditions($where);
        return $this->db->get($this->db,null,$columns);
    }

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return \EasySwoole\Mysqli\Mysqli|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function findWhereIn(string $field, array $values, string $columns = '*'){
        return $this->db->whereIn($field,$values)->get($this->table,null,$columns);
    }

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return \EasySwoole\Mysqli\Mysqli|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function findWhereNotIn(string $field, array $values,string $columns = '*'){
        return $this->db->whereNotIn($field,$values)->get($this->table,null,$columns);
    }

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return \EasySwoole\Mysqli\Mysqli|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \EasySwoole\Mysqli\Exceptions\WhereParserFail
     * @throws \Throwable
     */
    public function findWhereBetween(string $field, array $values,string $columns = '*'){
        return $this->db->whereBetween($field,$values)->get($this->table,null,$columns);
    }

    /**
     * @param string $columns
     * @return Connection|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function findByCriteria(string $columns = '*'){
        return $this->db->get($this->table,null,$columns);
    }
    

    /**
     * @param array $attributes
     * @return int|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function create(array $attributes){
        $this->db->insert($this->table,$attributes);
        return $this->db->getInsertId();
    }

    /**
     * @param array $attributes
     * @param int $id
     * @return mixed
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function update(array $attributes,int $id){
        $this->applyCriteria();
        return $this->db->where('id',$id)->update($this->table,$attributes);
    }

    /**
     * @param int $id
     * @return bool|mixed|null
     * @throws \EasySwoole\Mysqli\Exceptions\ConnectFail
     * @throws \EasySwoole\Mysqli\Exceptions\PrepareQueryFail
     * @throws \Throwable
     */
    public function delete(int $id){
        return $this->db->where('id',$id)->delete($this->table);
    }


    /**
     * @param string $column
     * @param string $direction
     * @return $this|mixed
     * @throws \EasySwoole\Mysqli\Exceptions\OrderByFail
     */
    public function orderBy(string $column,string $direction = 'asc'){
        $this->db->orderBy($column,$direction);
        return $this;
    }


    /**
     * @param $criteria
     * @return $this
     */
    public function pushCriteria($criteria){
        if(is_string($criteria) && class_exists($criteria)){
            $criteria = new $criteria;
        }
        if(!$criteria instanceof CriteriaInterface){

        }
        $this->criteria[] = $criteria;
        return $this;
    }

    protected function applyCriteria(){

        if(sizeof($this->criteria) > 0) {
            foreach ($this->criteria as $criterion) {
                if ($criterion instanceof CriteriaInterface) {
                    $criterion->apply($this->db);
                }
            }
            $this->resetCriteria();
        }
        return $this;
    }

    protected function resetCriteria(){
        $this->criteria = [];
    }


    /**
     * @param array $where
     */
    protected function applyConditions(array $where)
    {
        foreach ($where as $k=>$value){
            if(!is_array($value)){
                $this->db->where($k,$value);
            }
            else{
                list($condition,$val) = $value;
                $this->db->where($k,$val,$condition);
            }
        }
    }

}