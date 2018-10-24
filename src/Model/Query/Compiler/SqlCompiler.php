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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Query;

abstract class SqlCompiler implements CompilerInterface, SqlCompilableinterface
{
    public $quotable = false;

    /**
     * @var Query
     */
    public $query;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function __construct(Query $query, ConnectionInterface $connection)
    {
        $this->query = $query;
        $this->connection = $connection;
    }

    public function compile(SqlCompilableinterface $node)
    {
        return $node->asSql($this, $this->connection);
    }

    /**
     * Returns True if this field should be used to descend deeper for selectRelated() purposes.
     *
     * @param Field $field      the field to be checked
     * @param bool  $restricted indicating if the field list has been manually restricted using a requested clause
     * @param array $requested  The selectRelated() array
     * @param bool  $reverse    True if we are checking a reverse select related
     *
     * @return bool
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function selectRelatedDescend(Field $field, $restricted, $requested, $reverse = false)
    {
        if (!$field->relation) {
            return false;
        }

        if ($field->relation->parentLink && !$reverse) {
            return false;
        }

        if ($restricted) {
            if ($reverse && !array_key_exists($field->getRelatedQueryName(), $requested)) {
                return false;
            }
            if (!$reverse && !array_key_exists($field->getName(), $requested)) {
                return false;
            }
        }

        if (!$restricted && $field->isNull()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function quoteUnlessAliasCallback()
    {
        return function ($name) {
            if (array_key_exists($name, $this->query->tableAlias) &&
                !array_key_exists($name, $this->query->tableJoinsMap)) {
                return $name;
            }
            return $this->canQuote() ? $this->connection->quoteIdentifier($name) : $name;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function quoteCallback()
    {
        return function ($name) {
            return $this->canQuote() ? $this->connection->quoteIdentifier($name) : $name;
        };
    }

    private function canQuote()
    {
        return method_exists($this->connection, 'quoteIdentifier');
    }
}
