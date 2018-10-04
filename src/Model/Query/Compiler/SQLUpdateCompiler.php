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
use Doctrine\DBAL\Query\QueryBuilder;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\UpdateQuery;

class SQLUpdateCompiler extends SqlFetchBaseCompiler
{
    /**
     * @var UpdateQuery
     */
    public $query;

    public function executeSql($chunked = false)
    {
        return $this->asSql()->execute();
    }

    /**
     * if the instance passed to a compiler it can be converted into a valid Sql string.
     *
     * @param CompilerInterface $compiler
     * @param Connection        $connection
     *
     * @return QueryBuilder
     *
     * @throws TypeError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function asSql(CompilerInterface $compiler = null, ConnectionInterface $connection = null)
    {
        $connection = $this->connection;

        $this->preSqlSetup();
        $qb = $connection->createQueryBuilder();
        $qb->update($this->query->tablesAliasList[0]);
        $params = [];

        /* @var $field Field */
        /* @var $model Model */
        foreach ($this->query->getValues() as $valItem) {
            $field = $valItem[0];
            $model = $valItem[1];
            $value = $valItem[2];

            $name = $field->getColumnName();
            $qb->set($name, '?');

            //todo resolve_expression,
            if (method_exists($value, 'prepareDatabaseSave')) {
                if ($field->isRelation) {
                    $value = $field->prepareValueBeforeSave(
                        $value->prepareDatabaseSave($field),
                        $connection
                    );
                } else {
                    throw new TypeError(
                        "Tried to update field '%s' with a model instance, '%s'. Use a value compatible with '%s'.",
                        $field->getName(),
                        $value,
                        get_class($field)
                    );
                }
            } else {
                $value = $field->prepareValueBeforeSave($value, $connection);
            }
            // prepare value
            $params[] = $value;
        }

        list($sql, $whereParams) = $this->compile($this->where);
        $qb->where($sql);
        $params = array_merge($params, $whereParams);

        foreach ($params as $index => $param) {
            $qb->setParameter($index, $param);
        }

        return $qb;
    }
}
