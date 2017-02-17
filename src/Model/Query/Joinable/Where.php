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
    private $conditions =[];

    public static function createObject($kwargs=[])
    {
        return new self;
    }
    public function asSql(Connection $connection)
    {

        $whereSql = [];
        $whereParams = [];

        /* @var $lookup BaseLookup */
        foreach ($this->conditions as $connector=>$lookup) :
//            foreach ($conditions as $connector => $lookup) :
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
//            endforeach;

        endforeach;

        return [implode(' ', $whereSql), $whereParams];
    }

    /**
     * @param array $conditions
     */
    public function setConditions($connector, $conditions)
    {
        $this->conditions[$connector] = $conditions;
    }

}