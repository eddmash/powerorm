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
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\MultipleObjectsReturned;
use Eddmash\PowerOrm\Exception\NotSupported;
use Eddmash\PowerOrm\Exception\ObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
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
    protected $model;

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

    public function __construct(Connection $connection = null, Model $model = null, Query $query = null, $kwargs = [])
    {
        $this->connection = (is_null($connection)) ? $this->getConnection() : $connection;
        $this->model = $model;
        $this->query = ($query == null) ? $this->getQueryBuilder() : $query;
    }

    private function getConnection()
    {
        return BaseOrm::getDbConnection();
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
    public static function createObject(
        Connection $connection = null,
        Model $model = null,
        Query $query = null,
        $kwargs = []
    ) {
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
            throw new ObjectDoesNotExist(
                sprintf(
                    '%s matching query does not exist.',
                    $this->model->meta->modelName
                )
            );
        endif;

        throw new MultipleObjectsReturned(
            sprintf(
                '"get() returned more than one %s -- it returned %s!"',
                $this->model->meta->modelName,
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
        return $this->_filterOrExclude(false, func_get_args());
    }

    /**
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function annotate()
    {
        $args = func_get_args();

        return $this;
    }

    public function aggregate($kwargs = [])
    {
        $query = $this->query->deepClone();
        foreach ($kwargs as $alias => $annotation) :
            $query->addAnnotation(['annotation' => $annotation, 'alias' => $alias, 'isSummary' => true]);
            if (!$query->annotations[$alias]->containsAggregate) :
                throw new TypeError(sprintf('%s is not an aggregate expression', $alias));
            endif;
        endforeach;

        return $query->getAggregation($this->connection, array_keys($kwargs));
    }

    public function selectRelated($fields = [])
    {
        //todo if we implement values/values_list check we dont call this after it
        $obj = $this->_clone();
        if (empty($fields)):
            $obj->query->addSelectRelected($fields);
        elseif ($fields):
            $obj->query->selectRelected = false;
        else:
            $obj->query->selectRelected = true;
        endif;

        return $obj;
    }

    public function exclude()
    {
        return $this->_filterOrExclude(true, func_get_args());
    }

    public function exists()
    {
        if (!$this->_resultsCache):
            $instance = $this->all()->limit(0, 1);

            return (bool)$instance->query->execute($this->connection)->fetch();
        endif;

        return (bool)$this->_resultsCache;
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

        $sql = str_replace('?', '%s', $sql);

        return sprintf($sql, implode(', ', $params));
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

            $this->_resultsCache = $this->mapResults();

            $this->_evaluated = true;
        endif;

        return $this->_resultsCache;
    }

    public function _toSql()
    {

        $clone = $this->values([$this->model->meta->primaryKey->getColumnName()]);

        return $clone->query->getNestedSql($this->connection);
    }

    public function values()
    {
        $clone = $this->_clone();
        $fields = func_get_args();
        $fields = (empty($fields)) ? [] : $fields[0];

        if ($fields):
            $clone->query->clearSelectedFields();
            $clone->query->useDefaultCols = false;
        else:
            foreach ($this->model->meta->getConcreteFields() as $field) :
                $fields[] = $field->getAttrName();
            endforeach;

        endif;
        $clone->query->setValueSelect($fields);
        $clone->query->addFields($fields, true);

        return $clone;
    }

    private function addConditions($negate, $conditions)
    {
        foreach ($conditions as $condition) :
            $this->query->addConditions($condition, $negate);
        endforeach;
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
    private function mapResults()
    {
        $results = $this->query->execute($this->connection)->fetchAll();
        $klassInfo = $this->query->klassInfo;
        $modelClass = ArrayHelper::getValue($klassInfo, 'modelClass');
        /* @var $modelClass Model */
        $mapped = [];
        foreach ($results as $result) :
            $obj = $modelClass::fromDb($result);
            $mapped[] = $obj;
        endforeach;

        return $mapped;
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
        $qb = clone $this->query;

        return self::createObject($this->connection, $this->model, $qb);
    }
}
