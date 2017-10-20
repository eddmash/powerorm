<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\InvalidArgumentException;
use Eddmash\PowerOrm\Exception\MultipleObjectsReturned;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\NotSupported;
use Eddmash\PowerOrm\Exception\ObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\Node;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;
use function Eddmash\PowerOrm\Model\Query\Expression\not_;
use function Eddmash\PowerOrm\Model\Query\Expression\q_;
use Eddmash\PowerOrm\Model\Query\Results\ArrayMapper;
use Eddmash\PowerOrm\Model\Query\Results\ArrayValueMapper;
use Eddmash\PowerOrm\Model\Query\Results\ModelMapper;

const PRIMARY_KEY_ID = 'pk';

function getFieldNamesFromMeta(Meta $meta)
{
    $fieldNames = [];
    /** @var $field Field */
    foreach ($meta->getFields() as $field) :
        $fieldNames[] = $field->getName();
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
    public $connection;

    /**
     * @var Model
     */
    public $model;

    public $resultMapper;

    /**
     * @var Query
     */
    public $query;

    public $_evaluated = false;

    /**
     * @var mixed Holds the Queryset Result when Queryset evaluates
     *
     * @internal
     */
    protected $_resultsCache;
    private $_fields;
    protected $kwargs = [];

    public function __construct(Connection $connection = null, Model $model = null, Query $query = null, $kwargs = [])
    {
        $this->connection = (is_null($connection)) ? $this->getConnection() : $connection;
        $this->model = $model;
        $this->query = ($query == null) ? $this->getQueryBuilder() : $query;
        $this->resultMapper = ArrayHelper::pop($kwargs, 'resultMapper', ModelMapper::class);
        $this->kwargs = $kwargs;
    }

    private function getConnection()
    {
        return BaseOrm::getDbConnection();
    }

    /**
     * @param Connection $connection
     * @param Model      $model
     * @param Query      $query
     * @param array      $kwargs
     *
     * @return self
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject(
        Connection $connection = null,
        Model $model = null,
        Query $query = null,
        $kwargs = []
    ) {
        return new static($connection, $model, $query, $kwargs);
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
        $queryset = $this->_filterOrExclude(false,
            static::formatFilterConditions(__METHOD__, func_get_args()));

        $resultCount = count($queryset);
        if ($resultCount == 1):
            return $queryset->getResults()[0];
        elseif (!$resultCount):
            throw new ObjectDoesNotExist(
                sprintf(
                    '%s matching query does not exist.',
                    $this->model->meta->getNamespacedModelName()
                )
            );
        endif;

        throw new MultipleObjectsReturned(
            sprintf(
                '"get() returned more than one %s -- it returned %s!"',
                $this->model->meta->getNamespacedModelName(),
                $resultCount
            )
        );
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
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function filter()
    {
        return $this->_filterOrExclude(false,
            static::formatFilterConditions(__METHOD__, func_get_args()));
    }

    /**
     * Return a query set in which the returned objects have been annotated with extra data or aggregations.
     *
     * @return Queryset
     *
     * @throws ValueError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function annotate()
    {
        $args = static::formatFilterConditions(__METHOD__, func_get_args());
        $names = $this->_fields;
        if (is_null($this->_fields)):
            $names = [];
            foreach ($this->model->meta->getFields() as $field) :
                $names[] = $field->getName();
            endforeach;
        endif;
        $clone = $this->_clone();
        foreach ($args as $alias => $arg) :
            if (in_array($alias, $names)):
                throw new ValueError(
                    sprintf("The annotation '%s' conflicts with a field on the model.", $alias));
            endif;
            $clone->query->addAnnotation(['annotation' => $arg, 'alias' => $alias, 'isSummary' => false]);
        endforeach;

        //todo group by
        foreach ($clone->query->annotations as $alias => $annotation) :

            if ($annotation->containsAggregates() && in_array($alias, $args)):
                if (is_null($clone->_fields)):
                    $clone->query->groupBy = true;
                else:
                    $clone->query->setGroupBy();
                endif;
            endif;
        endforeach;

        return $clone;
    }

    /**
     * Returns a new QuerySet instance with the ordering changed.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @param array $fieldNames
     *
     * @return Queryset
     */
    public function orderBy($fieldNames = array())
    {
        assert($this->query->isFilterable(), 'Cannot reorder a query once a slice has been taken.');
        $clone = $this->_clone();
        $clone->query->clearOrdering(false);
        $clone->query->addOrdering($fieldNames);

        return $clone;
    }

    public function aggregate($kwargs = [])
    {
        //todo accept non associative items
        $query = $this->query->deepClone();
        foreach ($kwargs as $alias => $annotation) :
            $query->addAnnotation(['annotation' => $annotation, 'alias' => $alias, 'isSummary' => true]);
            // ensure we have an aggrated function
            if (!$query->annotations[$alias]->containsAggregates()) :
                throw new TypeError(sprintf('%s is not an aggregate expression', $alias));
            endif;
        endforeach;

        return $query->getAggregation($this->connection, array_keys($kwargs));
    }

    /**
     * Returns a new QuerySet instance that will select related objects.
     *
     * If fields are specified, they must be ForeignKey fields and only those related objects are included in the
     * selection.
     *
     * If select_related(null) is called, the list is cleared.
     *
     *
     * @param array $fields
     *
     * @return Queryset
     *
     * @throws InvalidArgumentException
     * @throws TypeError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function selectRelated($fields = [])
    {
        Tools::ensureParamIsArray($fields);

        if ($this->_fields) :
            throw new TypeError('Cannot call selectRelated() after .values() or .asArray()');
        endif;
        $obj = $this->_clone();

        if (empty($fields)):
            $obj->query->selectRelected = false;
        elseif ($fields):
            $obj->query->addSelectRelected($fields);
        else:
            $obj->query->selectRelected = true;
        endif;

        return $obj;
    }

    public function prefetchRelated()
    {
        throw new NotImplemented(__METHOD__.' NOT IMPLEMENTED');
    }

    public function exclude()
    {
        return $this->_filterOrExclude(true,
            static::formatFilterConditions(__METHOD__, func_get_args()));
    }

    public function exists()
    {
        if (!$this->_resultsCache):
            $instance = $this->all()->limit(0, 1);

            return (bool) $instance->query->execute($this->connection)->fetch();
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
        /** @var $clone UpdateQuery */
        $clone = $this->query->deepClone(UpdateQuery::class);
        $clone->addUpdateFields($records);

        return $clone->execute($this->connection);
    }

    public function _insert($model, $fields, $returnId)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->insert($model->meta->dbTable);

        /** @var $field Field */
        foreach ($fields as $name => $field) :
            $value = $this->prepareValueForDatabaseSave($field, $field->preSave($model, true));

            $qb->setValue($field->getColumnName(), $qb->createNamedParameter($value));
        endforeach;

        // save to db
        $qb->execute();

        if ($returnId):
            return $this->connection->lastInsertId();
        endif;
    }

    /**
     * @param Field $field
     * @param $preSave
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @return mixed
     */
    private function prepareValueForDatabaseSave(Field $field, $value)
    {
        return $field->prepareValueBeforeSave($value, $this->connection);
    }

    protected function _filterOrExclude($negate, $conditions)
    {
        $instance = $this->_clone();

        if ($negate):
            $instance->query->addQ(not_($conditions));
        else:
            $instance->query->addQ(q_($conditions));
        endif;

        return $instance;
    }

    /**
     * Ensure the conditions passed in are ready to used to perform query operations.
     *
     * @param $methondname
     * @param $conditions
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function formatFilterConditions($methondname, $conditions)
    {
        if (count($conditions) > 1):
            throw new InvalidArgumentException(
                sprintf("Method '%s' supports a single array input", $methondname));
        endif;

        if (count($conditions) == 1):
            if ($conditions[0] instanceof Node):
                return $conditions;
            endif;
        endif;

        return call_user_func_array('array_merge', $conditions);
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

        list($sql, $params) = $instance->query->getSqlCompiler($this->connection)->asSql();

        $sql = str_replace('?', '%s', $sql);

        return vsprintf($sql, $params);
    }

    /**
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getResults()
    {
        if (false === $this->_evaluated):
            $this->_resultsCache = call_user_func($this->getMapper());

            $this->_evaluated = true;
        endif;

        return $this->_resultsCache;
    }

    public function getMapper()
    {
        return new $this->resultMapper($this);
    }

    public function _toSql()
    {

        $clone = $this->asArray([$this->model->meta->primaryKey->getColumnName()]);

        return $clone->query->getNestedSql($this->connection);
    }

    public function asArray($fields = [], $valuesOnly = false)
    {
        $clone = $this->_clone();
        $clone->_fields = $fields;
        if ($fields):
            $clone->query->clearSelectedFields();
            $clone->query->useDefaultCols = false;
        else:
            foreach ($this->model->meta->getConcreteFields() as $field) :
                $fields[] = $field->getName();
            endforeach;

        endif;

        $clone->query->setValueSelect($fields);
        $clone->query->addFields($fields, true);

        $clone->resultMapper = ($valuesOnly) ? ArrayValueMapper::class : ArrayMapper::class;

        return $clone;
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
        if ($this->_resultsCache):
            return count($this->_resultsCache);
        endif;

        return $this->query->getCount($this->connection);
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
    public function _clone()
    {
        $qb = $this->query->deepClone();

        $kwargs = array_merge(['resultMapper' => $this->resultMapper], $this->kwargs);

        return self::createObject($this->connection, $this->model, $qb, $kwargs);
    }

    public function __toString()
    {
        $results = $this->_clone();
        if (!$results->query->limit && !$results->query->offset) :
            $results = $results->limit(1, 6);
        endif;

        $results = $results->getResults();

        $ellipse = count($results) > 5 ? ', ... ' : '';

        return sprintf('< %s (%s %s) >', get_class($this), implode(', ', $results), $ellipse);
    }

    public function __debugInfo()
    {
        return $this->_clone()->getResults();
    }

    /**
     * Ready this instance for use as argument in filter.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _prepareAsFilterValue()
    {
        if(is_null($this->_fields)):
            $queryset = $this->asArray(['pk']);
        else:
            if(count($this->_fields) > 1):
                throw new TypeError('Cannot use multi-field values as a filter value.');
            endif;
            $queryset = $this->_clone();
        endif;

        return $queryset->query->toSubQuery($queryset->connection);
    }
}
