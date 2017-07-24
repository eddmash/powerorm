<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Joinable;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Helpers\Node;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;

class WhereNode extends Node
{
    protected $defaultConnector = AND_CONNECTOR;

    public function asSql(Connection $connection)
    {
        $whereSql = [];
        $whereParams = [];

        /* @var $lookup BaseLookup */
        foreach ($this->getChildren() as $child) :
            list($sql, $parms) = $child->asSql($connection);

            $whereSql[] = $sql;
            if (!is_array($parms)):
                $parms = [$parms];
            endif;
            $whereParams = array_merge($whereParams, $parms);

        endforeach;

        $conn = sprintf(' %s ', $this->connector);
        $whereSqlString = implode($conn, $whereSql);
        if ($whereSqlString):
            if ($this->isNegated()):
                $whereSqlString = sprintf('NOT (%s)', $whereSqlString);
            elseif (count($whereSql) > 1):
                $whereSqlString = sprintf('(%s)', $whereSqlString);
            endif;
        endif;

        return [$whereSqlString, $whereParams];
    }

//    public function setConditions($connector, $conditions)
//    {
//        $this->conditions[] = [$connector, $conditions];
//    }

    public function deepClone()
    {
        $obj = new self();
//        foreach ($this->conditions as $conditionInfo) :$whereSql
//            list($conector, $condition) = $conditionInfo;
//            if (method_exists($condition, 'deepClone')) :
//                $obj->setConditions($conector, $condition->deepClone());
//            else:
//                $obj->setConditions($conector, $condition);
//            endif;
//        endforeach;

        return $obj;
    }
}
