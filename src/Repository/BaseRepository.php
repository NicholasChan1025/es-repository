<?php


namespace Infrastructure\Repository\Repository;


use EasySwoole\Component\Singleton;
use EasySwoole\MysqliPool\Connection;
use EasySwoole\MysqliPool\Mysql;


class BaseRepository
{
    use Singleton;

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
        $this->db = Mysql::getInstance()->pool('mysql')::defer();
    }



}