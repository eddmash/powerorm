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

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;
use const Eddmash\PowerOrm\Model\Query\INNER;
use const Eddmash\PowerOrm\Model\Query\LOUTER;

class Join extends BaseJoin
{
    /**
     * @var RelatedField|ForeignObjectRel
     */
    private $joinField;

    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        $quoteUnlessAliasCallback = $compiler->quoteUnlessAliasCallback();
        $quoteCallback = $compiler->quoteCallback();
        $joinConditions = [];
        $fields = [$this->joinField->getJoinColumns()];
        /* @var $from RelatedField */
        /* @var $to RelatedField */
        foreach ($fields as $index => $relFields) {
            list($from, $to) = $relFields;
            $joinConditions[] = sprintf(
                ' %s.%s = %s.%s',
                $quoteUnlessAliasCallback($this->getParentAlias()),
                $quoteCallback($from->getColumnName()),
                $quoteUnlessAliasCallback($this->getTableAlias()),
                $quoteCallback($to->getColumnName())
            );
        }

        $onClauseSql = implode(' AND ', $joinConditions);
        $alias = $this->tableAlias == $this->tableName ? '' : $this->tableAlias;
        $sql = sprintf('%s %s%s ON (%s)', $this->getJoinType(),
            $quoteUnlessAliasCallback($this->getTableName()),
            $alias, $onClauseSql);

        return [$sql, []];
    }

    /**
     * @return mixed
     */
    public function getJoinField()
    {
        return $this->joinField;
    }

    /**
     * @param mixed $joinField
     */
    public function setJoinField($joinField)
    {
        $this->joinField = $joinField;
    }

    /**
     * Change join to inner join.
     *
     * @return Join
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function demote()
    {
        $join = $this->relabeledClone();
        $join->setJoinType(INNER);

        return $join;
    }

    /**
     * Change join to left outer join.
     *
     * @return Join
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function promote()
    {
        $join = $this->relabeledClone();
        $join->setJoinType(LOUTER);

        return $join;
    }

    /**
     * Clone join with the option of relabeling the aliases.
     *
     * @param array $changeMap
     *
     * @return static
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function relabeledClone($changeMap = [])
    {
        $newParentAlias = ArrayHelper::getValue($changeMap, $this->parentAlias, $this->parentAlias);
        $tableAlias = ArrayHelper::getValue($changeMap, $this->tableAlias, $this->tableAlias);
        $join = new static();
        $join->setTableName($this->getTableName());
        $join->setParentAlias($newParentAlias);
        $join->setTableAlias($tableAlias);
        $join->setJoinType($this->getJoinType());
        $join->setJoinField($this->getJoinField());
        $join->setNullable($this->getNullable());

        return $join;
    }

    /**
     * Check if pass in item is equal to this one.
     *
     * @param $item
     *
     * @return bool
     */
    public function equal($item)
    {
        if ($item instanceof static) {
            $v = $this->tableName == $item->tableName;
            return
                $this->tableName == $item->tableName &&
                $this->parentAlias == $item->parentAlias &&
                get_class($this->joinField) === get_class($item->joinField);
        }

        return false;
    }
}
