<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\MultipleObjectsReturned;
use Eddmash\PowerOrm\Exception\NotSupported;
use Eddmash\PowerOrm\Exception\ObjectDoesNotExist;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Lookup\LookupInterface;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

const PRIMARY_KEY_ID = 'pk';

function getFieldNamesFromMeta(Meta $meta)
{
    $fieldNames = [];
    /** @var $field Field */
    foreach ($meta->getFields() as $field) :
        $fieldNames[] = $field->name;
    endforeach;

    return $fieldNames;
}
/**
 * Represents a lazy database lookup for a set of objects.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Queryset implements QuerysetInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Model
     */
    private $model;

    /**
     * @var Query
     */
    private $query;

    public $_evaluated = false;

    /**
     * @var mixed Holds the Queryset Result when Queryset evaluates
     *
     * @internal
     */
    protected $_resultsCache;

    public function __construct(Connection $connection, Model $model, Query $query = null)
    {
        $this->connection = $connection;
        $this->model = $model;
        $this->query = ($query == null) ? $this->getQueryBuilder() : $query;
    }

    /**
     * @param $connection
     * @param $model
     *
     * @return static
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($connection, $model, $query = null)
    {
        return new static($connection, $model, $query);
    }

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id', 'p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'u.id = p.user_id');
     * </code>
     */
    public function only($select = null)
    {
        $selects = is_array($select) ? $select : func_get_args();
        $this->query->addSelect($selects, true);
    }

    public function get()
    {
        $queryset = $this->_filterOrExclude(false, func_get_args());
        $resultCount = count($queryset);

        if ($resultCount == 1):
            return $queryset->getResults()[0];
        elseif (!$resultCount):
            throw new ObjectDoesNotExist(sprintf('%s matching query does not exist.',
                $this->model->meta->modelName));
        endif;

        throw new MultipleObjectsReturned(sprintf('"get() returned more than one %s -- it returned %s!"',
            $this->model->meta->modelName, $resultCount));
    }

    /**
     * This method takes associative array as parameters. or an assocative array whose first item is the connector to
     * use for the generated where conditions, Valid choices are :.
     *
     * <code>
     *   Role::objects()->filter(
     *      ["name"=>"asdfasdf"],
     *      ["or","name__not"=>"tr"],
     *      ["and","name__in"=>"we"],
     *      ["or", "name__contains"=>"qwe"]
     *  );
     * </code>
     *
     * @param null $conditions
     *
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function filter()
    {
        return $this->_filterOrExclude(false, func_get_args());
    }

    public function with($conditions = null)
    {
        return $this;
    }

    public function exclude()
    {
        return $this->_filterOrExclude(true, func_get_args());
    }

    public function exists()
    {
        if (!$this->_resultsCache):
            $instance = $this->all()->limit(0, 1);
            $this->_resultsCache = $instance->execute();
        endif;

        return (bool) $this->_resultsCache;
    }

    public function limit($start, $end)
    {
        $this->query->setLimit($start, $end);

        return $this;
    }

    public function update()
    {
    }

    public function _update($records)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->update($this->model->meta->dbTable);
        $params = [];
        foreach ($records as $name => $value) :
            $qb->set($name, '?');
            $params[] = $value;
        endforeach;

        list($sql, $whereParams) = $this->query->getWhereSql($this->connection);
        $qb->where($sql);
        $params = array_merge($params, $whereParams);
        foreach ($params as $index => $param) :
            $qb->setParameter($index, $param);
        endforeach;

        return $qb->execute() > 0;
    }

    public function _insert($model, $fields, $returnId)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->insert($model->meta->dbTable);

        /** @var $field Field */
        foreach ($fields as $name => $field) :

            $qb->setValue($field->getColumnName(), $qb->createNamedParameter($field->preSave($model, true)));
        endforeach;

        // save to db
        $qb->execute();

        if ($returnId):
            return $this->connection->lastInsertId();
        endif;
    }

    public function _filterOrExclude($negate, $conditions)
    {
        $instance = $this->_clone();
        $instance->addConditions($negate, $conditions);

        return $instance;
    }

    public function validateConditions($conditions)
    {
        foreach ($conditions as $condition) :

        endforeach;
    }

    /**
     * Returns a new QuerySet that is a copy of the current one.
     *
     * This allows a QuerySet to proxy for a model manager in some cases.
     *
     * @return $this
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function all()
    {
        return $this->_clone();
    }

    /**
     * Gets the complete SQL string formed by the current specifications of this QueryBuilder.
     *
     * <code>
     *     $qb = User::objects()
     *         ->select('u')
     *         ->from('User', 'u')
     *     echo $qb->getSQL(); // SELECT u FROM User u
     * </code>
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getSql()
    {
        $instance = $this->_clone();

        list($sql, $params) = $instance->query->asSql($this->connection);

        return $sql;
    }

    /**
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getResults()
    {
        if (false === $this->_evaluated):

            $this->_resultsCache = $this->mapResults($this->model, $this->execute()->fetchAll());

            $this->_evaluated = true;
        endif;

        return $this->_resultsCache;
    }

    public function toSql()
    {
        $clone = $this->_clone();
        $clone->values($this->model->meta->primaryKey->getColumnName());

        return $clone->query->getNestedSql($this->connection);
    }

    public function values()
    {
        $fields = func_get_args();

        if ($fields):
            $this->query->setDefaultCols(false);
            $this->query->addSelect($fields, true);
        endif;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function execute()
    {
        list($sql, $params) = $this->query->asSql($this->connection);

        $stmt = $this->connection->prepare($sql);
        foreach ($params as $index => $value) :

            ++$index; // Columns/Parameters are 1-based, so need to start at 1 instead of zero
            $stmt->bindValue($index, $value);
        endforeach;

        $stmt->execute();

        return $stmt;
    }

    private function addConditions($negate, $conditions)
    {
        foreach ($conditions as $condition) :
            $this->buildFilter($condition, $negate);
        endforeach;
    }

    /**
     * @param $condition
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildFilter($condition, $negate = false)
    {
        foreach ($condition as $name => $value) :
            list($connector, $lookup, $field) = $this->solveLookupType($name);

            $condition = $this->buildCondition($lookup, $field, $value);

            $this->query->addWhere($condition, $connector);
        endforeach;
    }

    /**
     * @param $lookup
     * @param $rhs
     * @param $lhs
     *
     * @return LookupInterface
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildCondition($lookup, $rhs, $lhs)
    {
        /* @var $lookup LookupInterface */
        $lookup = $lookup::createObject($rhs, $lhs);

        return $lookup;
    }

    /**
     * Gets a filter field and returns the an array consisting of :
     * - the where clause connector to use e.g. and/ or.
     * - the looku object.
     *
     * @param $name
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function solveLookupType($name)
    {
        // get lookupand field
        if (preg_match(BaseLookup::$lookupPattern, $name)):
            list($name, $lookup) = preg_split(BaseLookup::$lookupPattern, $name);
        else:
            $lookup = 'exact';
        endif;

        // get connector
        list($connector, $name) = $this->getConnector($name);
        //todo check for span relationships
        $name = $this->validateField($name, $this->model->meta);
        $field = $this->getLookupField($name);
        $lookup = $field->getLookup($lookup);

        return [$connector, $lookup, $field];
    }

    /**
     * @param $name
     * @param Meta $meta
     *
     * @return
     *
     * @throws FieldError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function validateField($name, Meta $meta)
    {

        if ($name === PRIMARY_KEY_ID):
            $name = $meta->primaryKey->name;
        endif;

        $field = null;
        try {

            $field = $meta->getField($name);
        } catch (FieldDoesNotExist $e) {
            $available = getFieldNamesFromMeta($meta);
            throw new FieldError(sprintf("Cannot resolve keyword '%s' into field. Choices are: [ %s ]", $name,
                implode(', ', $available)));
        }

        return $field->name;
    }

    /**
     * @param $name
     *
     * @return \Eddmash\PowerOrm\Model\Field\Field
     *
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getLookupField($name)
    {
        //todo might need to look up the parent
        return $this->model->meta->getField($name);
    }

    /**
     * Determines the where clause connector to use.
     *
     * @param $name
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getConnector($name)
    {
        $connector = BaseLookup::AND_CONNECTOR;

        // get the actual key
        if (preg_match(BaseLookup::$whereConcatPattern, $name)):
            // determine how to combine where statements
            list($lookup, $name) = preg_split(BaseLookup::$whereConcatPattern, $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $connector = BaseLookup::OR_CONNECTOR;
        endif;

        return [$connector, $name];
    }

    /**
     * @return Query
     *
     * @throws \Eddmash\PowerOrm\Exception\NotImplemented
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getQueryBuilder()
    {
        return Query::createObject($this->model);
    }

    /**
     * @param Model $model
     * @param array $results
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function mapResults($model, $results)
    {
        /* @var $newModel Model */
        $mapped = [];
        foreach ($results as $result) :
            $mapped[] = $this->mapResult($model, $result);
        endforeach;

        return $mapped;
    }

    private function mapResult($model, $result)
    {
        /** @var $modelName Model */
        $modelName = $model->meta->modelName;

        return $modelName::fromDb($modelName, $result);
    }

    // **************************************************************************************************

    // ************************************** MAGIC METHODS Overrides ***********************************

    // **************************************************************************************************

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement
     * executed by the corresponding object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement,
     * some databases may return the number of rows returned by that statement. However,
     * this behaviour is not guaranteed for all databases and should not be
     * relied on for portable applications.
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function count()
    {
        $instance = $this->_clone();
        $instance->query->addSelect('count(*)', true);

        return $instance->execute()->fetchColumn(0);
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a foreach.
     *
     * @ignore
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->getResults();

        return new \ArrayIterator($this->_resultsCache);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->getResults();

        return isset($this->_resultsCache[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $exists = $this->offsetExists($offset);

        return isset($exists) ? $this->_resultsCache[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new NotSupported('set/unset operations are not supported by Queryset');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new NotSupported('set/unset operations are not supported by Queryset');
    }

    /**
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _clone()
    {
        $qb = clone $this->query;

        return self::createObject($this->connection, $this->model, $qb);
    }
}
