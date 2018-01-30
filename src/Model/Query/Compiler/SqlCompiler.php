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
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Query;

abstract class SqlCompiler implements CompilerInterface, SqlCompilableinterface
{
    /**
     * @var Query
     */
    public $query;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function __construct(Query $query, Connection $connection)
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
        if (!$field->relation):
            return false;
        endif;

        if ($field->relation->parentLink && !$reverse):
            return false;
        endif;

        if ($restricted):
            if ($reverse && !array_key_exists($field->getRelatedQueryName(), $requested)):
                return false;
            endif;
            if (!$reverse && !array_key_exists($field->getName(), $requested)):
                return false;
            endif;
        endif;

        if (!$restricted && $field->isNull()):
            return false;
        endif;

        return true;
    }
}
