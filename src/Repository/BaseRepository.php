<?php


namespace Infrastructure\Repository\Repository;

use EasySwoole\MysqliPool\Connection;
use EasySwoole\MysqliPool\Mysql;
use Infrastructure\Repository\Contracts\AbstractCriteria;


class BaseRepository
{
    protected $table;

    /** @var Connection */
    protected $db;

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

    public function addCriteria(array $criteriaList){

        foreach ($criteriaList as $criteria){
            if($criteria instanceof AbstractCriteria){
                 $criteria->build($this->db);
            }
        }
        return $this;
    }

}