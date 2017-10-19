<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Joinable;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Model\Query\Compiler\SqlCovertableinterface;

abstract class BaseJoin implements SqlCovertableinterface
{
    protected $tableName;
    protected $tableAlias;
    protected $joinType;
    protected $parentAlias;
    protected $nullable;

    /**
     * @return mixed
     */
    public function getTableAlias()
    {
        return $this->tableAlias;
    }

    /**
     * @param mixed $tableAlias
     */
    public function setTableAlias($tableAlias)
    {
        $this->tableAlias = $tableAlias;
    }

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param mixed $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return mixed
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @param mixed $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }

    /**
     * @return mixed
     */
    public function getParentAlias()
    {
        return $this->parentAlias;
    }

    /**
     * @param mixed $parentAlias
     */
    public function setParentAlias($parentAlias)
    {
        $this->parentAlias = $parentAlias;
    }

    /**
     * @return mixed
     */
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * @param mixed $nullable
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
    }

    abstract public function asSql(Connection $connection);

}
