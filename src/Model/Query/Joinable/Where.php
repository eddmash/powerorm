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
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;

class Where
{
    private $conditions = [];

    public static function createObject($kwargs = [])
    {
        return new self();
    }

    public function asSql(Connection $connection)
    {

        $whereSql = [];
        $whereParams = [];

        /* @var $lookup BaseLookup */
        foreach ($this->conditions as $conditionInfo) :
            list($connector, $lookup) = $conditionInfo;
            // if we have another condition already added, add the connector
            if ($whereSql):
                $whereSql[] = $connector;
            endif;

            list($sql, $parms) = $lookup->asSql($connection);

            $whereSql[] = $sql;
            if (!is_array($parms)):
                $parms = [$parms];
            endif;
            $whereParams = array_merge($whereParams, $parms);

        endforeach;

        return [implode(' ', $whereSql), $whereParams];
    }

    public function setConditions($connector, $conditions)
    {
        $this->conditions[] = [$connector, $conditions];
    }

    public function deepClone()
    {
        $obj = new self();
        foreach ($this->conditions as $conditionInfo) :
            list($conector, $condition) = $conditionInfo;
            if (method_exists($condition, 'deepClone')) :
                $obj->setConditions($conector, $condition->deepClone());
            else:
                $obj->setConditions($conector, $condition);
            endif;
        endforeach;

        return $obj;
    }
}
