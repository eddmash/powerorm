<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Joinable;


use Doctrine\DBAL\Connection;

class BaseTable extends BaseJoin
{

    /**
     * BaseTable constructor.
     * @param $tableName
     * @param $tableAlias
     */
    public function __construct($tableName, $tableAlias)
    {
        $this->setTableName($tableName);
        $this->setTableAlias($tableAlias);
    }

    public function asSql(Connection $connection)
    {
        $tableAlias = "";
        if($this->getTableName() !== $this->getTableAlias()):
            $tableAlias = sprintf("%s", $this->getTableAlias());
        endif;
        $tableName = $this->getTableName();
        return [sprintf("%s %s", $tableName, $tableAlias), []];
    }
}