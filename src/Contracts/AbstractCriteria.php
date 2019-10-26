<?php


namespace Infrastructure\Repository\Contracts;

use EasySwoole\MysqliPool\Connection;

abstract class AbstractCriteria
{
    protected $queries;

    public static function create($queries = null){
        return new static($queries);
    }

    public function __construct($queries = null)
    {
        if(!empty($queries)){
            $this->queries = $queries;
        }
    }

    abstract public function build(Connection &$connection);
}