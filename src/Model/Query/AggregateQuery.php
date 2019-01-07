<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
