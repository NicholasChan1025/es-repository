<?php


namespace Infrastructure\Repository\Contracts;

use EasySwoole\Mysqli\QueryBuilder;

abstract class AbstractCriteria
{
    protected $queries;
    protected $builder;

    public static function create($queries = null){
        return new static($queries);
    }

    public function __construct($queries = null)
    {
        if(!empty($queries)){
            $this->queries = $queries;
        }
        $this->builder = new QueryBuilder();
    }

    abstract public function build();
}