<?php


namespace Infrastructure\Repository\Contracts;

use Infrastructure\Pool\Mysql\MysqlObject;

interface CriteriaInterface
{
    public function apply(MysqlObject &$db);
}