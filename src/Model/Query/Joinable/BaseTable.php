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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class BaseTable extends BaseJoin
{
    /**
     * BaseTable constructor.
     *
     * @param $tableName
     * @param $tableAlias
     */
    public function __construct($tableName, $tableAlias)
    {
        $this->setTableName($tableName);
        $this->setTableAlias($tableAlias);
    }

    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        $tableAlias = '';
        if ($this->getTableName() !== $this->getTableAlias()) {
            $tableAlias = sprintf('%s', $this->getTableAlias());
        }
        $tableName = $this->getTableName();

        $quoteCallback = $compiler->quoteUnlessAliasCallback();
        return [sprintf('%s %s', $quoteCallback($tableName), $tableAlias), []];
    }

    public function equal($item)
    {
        if ($item instanceof static) {
            return
                $this->tableName == $item->tableName;
        }

        return false;
    }
}
