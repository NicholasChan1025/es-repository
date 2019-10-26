<?php


namespace Infrastructure\Repository\Contracts;

use EasySwoole\MysqliPool\Connection;

abstract class AbstractCriteria
{
    protected $queries;
    protected $builder;

    public static function create(Connection $builder ,$queries = null){
        return new static($builder,$queries);
    }

    public function __construct(Connection $builder,$queries = null)
    {
        if(!empty($queries)){
            $this->queries = $queries;
        }
        $this->builder = $builder;
    }

    abstract public function build();
}