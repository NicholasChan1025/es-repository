<?php


namespace Infrastructure\Repository\Repository;

use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;


class BaseRepository extends AbstractModel
{
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