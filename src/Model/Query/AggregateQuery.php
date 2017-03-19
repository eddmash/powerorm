<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/19/17
 * Time: 2:06 PM.
 */
namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\EmptyResultSet;

class AggregateQuery extends Query
{
    public $subQuery;
    public $subQueryParams;

    public function addSubQuery(Query $query, Connection $connection)
    {
        list($this->subQuery, $this->subQueryParams) = $query->asSql($connection, true);
    }

    /**
     * {@inheritdoc}
     */
    public function asSql(Connection $connection, $isSubQuery = false)
    {
        if(is_null($this->isSubQuery)):
            throw new EmptyResultSet();
        endif;
        $sql = $params = [];

        foreach ($this->annotations as $annotation) {
            list($annSql, $annParam) = $annotation->asSql($connection);
            $sql[] = $annSql;
            $params = array_merge($params, $annParam);
        }
        $sql = implode(', ', $sql);

        $sql = sprintf('SELECT %s FROM (%s) subquery', $sql, $this->subQuery);

        return [$sql, $params];
    }

}
