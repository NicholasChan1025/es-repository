<?php


namespace Infrastructure\Repository\Repository;

use EasySwoole\MysqliPool\Connection;
use EasySwoole\MysqliPool\Mysql;
use Infrastructure\Repository\Contracts\AbstractCriteria;


class BaseRepository
{
    protected $table;
    protected $pk;

    /** @var Connection */
    protected $db;

    protected $alias;

    protected $fields;

    protected $limit;

    /**
     * BaseRepository constructor.
     * @throws \EasySwoole\Component\Pool\Exception\PoolEmpty
     * @throws \EasySwoole\Component\Pool\Exception\PoolException
     */
    public function __construct()
    {
        $this->db = Mysql::getInstance()::defer('mysql');
    }


    public static function create(){
        return new static();
    }


    protected function reset(){
        $this->alias = null;
        $this->fields = null;
        $this->limit = null;
    }

    protected function getAlias(){
        if(!empty($this->alias)){
            return $this->table." AS ".$this->alias;
        }
        else{
            return $this->table;
        }
    }

    public function addCriteria(array $criteriaList){

        foreach ($criteriaList as $criteria){
            if($criteria instanceof AbstractCriteria){
                $criteria->build($this->db);
            }
        }
        return $this;
    }


    public function alias(string $alias){
        $this->alias = $alias;
        return $this;
    }

    public function limit(int $page,int $limit){
        $this->limit = [$page,$limit];
        return $this;
    }

    public function withTotalCount(){
        $this->db->withTotalCount();
        return $this;
    }

    public function all(){
       $data =  $this->db->get($this->getAlias(),$this->limit,$this->fields);
       $total = $this->db->getTotalCount();
       $this->reset();
       return ['data'=>$data,'total'=>$total];
    }

    public function get(int $id = null){

        if(!empty($id)){
            $this->db->where('id',$id);
        }
        $data = $this->db->getOne($this->getAlias(),$this->fields);
        $this->reset();
        return $data;
    }

    public function update(array $data){
        if(!isset($data[$this->pk]) && empty($data[$this->pk])) throw new \Exception("pk is not exist");
        $pkValue = $data[$this->pk];
        $ret = $this->db->where($this->pk,$pkValue)->update($this->getAlias(),$data);
        $this->reset();
        return $ret ? true : false;
    }

    public function save(array $data){
        $ret = $this->db->insert($this->table,$data);
        if($ret){
            return $this->db->getInsertId();
        }
        else{
            return false;
        }
    }







}