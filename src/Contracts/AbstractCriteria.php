<?php


namespace Infrastructure\Repository\Contracts;

use EasySwoole\Mysqli\QueryBuilder;

abstract class AbstractCriteria
{
    protected $queries;
    protected $builder;

    public static function create(QueryBuilder $builder ,$queries = null){
        return new static($builder,$queries);
    }

    public function __construct(QueryBuilder $builder,$queries = null)
    {
        if(!empty($queries)){
            $this->queries = $queries;
        }
        $this->builder = $builder;
    }

    abstract public function build();
}