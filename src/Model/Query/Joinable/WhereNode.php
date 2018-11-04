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

use Eddmash\PowerOrm\CloneInterface;
use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Helpers\Node;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\SqlCompilableinterface;
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;

class WhereNode extends Node implements SqlCompilableinterface, CloneInterface
{
    protected $defaultConnector = AND_CONNECTOR;

    public function asSql(
        CompilerInterface $compiler,
        ConnectionInterface $connection
    ) {
        $whereSql = [];
        $whereParams = [];

        /* @var $lookup BaseLookup */
        foreach ($this->getChildren() as $child) {
            list($sql, $parms) = $compiler->compile($child);

            $whereSql[] = $sql;
            if (!is_array($parms)) {
                $parms = [$parms];
            }
            $whereParams = array_merge($whereParams, $parms);
        }

        $conn = sprintf(' %s ', $this->connector);
        $whereSqlString = implode($conn, $whereSql);
        if ($whereSqlString) {
            if ($this->isNegated()) {
                $whereSqlString = sprintf('NOT (%s)', $whereSqlString);
            } elseif (count($whereSql) > 1) {
                $whereSqlString = sprintf('(%s)', $whereSqlString);
            }
        }

        return [$whereSqlString, $whereParams];
    }

    //    public function setConditions($connector, $conditions)
    //    {
    //        $this->conditions[] = [$connector, $conditions];
    //    }

    public function deepClone()
    {
        $obj = new self();
        $obj->defaultConnector = $this->defaultConnector;
        $obj->negated = $this->negated;
        foreach ($this->getChildren() as $child) {
            if ($child instanceof CloneInterface) {
                $child = $child->deepClone();
            }
            $obj->getChildren()->add($child);
        }

        return $obj;
    }
}
