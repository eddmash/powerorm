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
use Eddmash\PowerOrm\Model\Field\RelatedField;

class Join extends BaseJoin
{
    /**
     * @var RelatedField
     */
    private $joinField;

    public function asSql(Connection $connection)
    {
        $joinConditions = [];
        $fields = [$this->joinField->getRelatedFields()];
        /**@var $from RelatedField*/
        /**@var $to RelatedField*/
        foreach ($fields as $index=>$relFields) :
            list($from, $to) = $relFields;
            $joinConditions[] = sprintf(" %s.%s = %s.%s", $this->getParentAlias(), $from->getColumnName(),
                $this->getTableAlias(), $to->getColumnName());
        endforeach;

        $onClauseSql = implode(" AND ", $joinConditions);
        $alias = "";
        $sql = sprintf('%s %s%s ON (%s)', $this->getJoinType(), $this->getTableName(), $alias, $onClauseSql);

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

}
