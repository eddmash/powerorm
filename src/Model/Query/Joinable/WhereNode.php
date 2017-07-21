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
//            if (!is_array($parms)):
//                $parms = [$parms];
//            endif;
//            $whereParams = array_merge($whereParams, $parms);

        endforeach;

        $whereSql = sprintf( "%s %s", $this->connector, implode(' ', $whereSql));
        dump($whereSql);
        return ['', $whereParams];
    }

//    public function setConditions($connector, $conditions)
//    {
//        $this->conditions[] = [$connector, $conditions];
//    }

    public function deepClone()
    {
        $obj = new self();
//        foreach ($this->conditions as $conditionInfo) :
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
