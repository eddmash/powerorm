<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/19/17
 * Time: 2:06 PM.
 */

namespace Eddmash\PowerOrm\Model\Query;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\SQLAggregateCompiler;

class AggregateQuery extends Query
{
    public $subQuery;

    public $subQueryParams;

    public function addSubQuery(Query $query, ConnectionInterface $connection)
    {
        $query->isSubQuery = true;
        list(
            $this->subQuery, $this->subQueryParams
            ) = $query->getSqlCompiler($connection)->asSql();
    }

    /**
     * {@inheritdoc}
     */
    public function getCompilerClass()
    {
        return SQLAggregateCompiler::class;
    }
}
