<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Compiler;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Eddmash\PowerOrm\Exception\EmptyResultSet;

class SQLAggregateCompiler extends SqlFetchBaseCompiler
{

    /**
     * {@inheritdoc}
     */
    public function asSql(Connection $connection, $isSubQuery = false)
    {

        $sql = $params = [];

        foreach ($this->annotations as $annotation) {
            list($annSql, $annParam) = $this->compile($annotation);
            $sql[] = $annSql;
            $params = array_merge($params, $annParam);
        }
        $sql = implode(', ', $sql);

        $sql = sprintf('SELECT %s FROM (%s) subquery', $sql, $this->query->subQuery);
        $params = array_merge($params, $this->query->subQueryParams);

        return [$sql, $params];
    }


}