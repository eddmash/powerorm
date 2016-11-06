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
use Doctrine\DBAL\Query\QueryBuilder;
use Eddmash\PowerOrm\Exception\NotSupported;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Model;
use PDO;

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
    private $qb;

    public $_evaluated = false;

    /**
     * @var mixed Holds the Queryset Result when Queryset evaluates
     *
     * @internal
     */
    protected $_resultsCache;

    public function __construct(Connection $connection, Model $model, QueryBuilder $qb = null)
    {
        $this->connection = $connection;
        $this->model = $model;
        $this->qb = ($qb == null) ? $this->getQueryBuilder() : $qb;
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
    public static function createObject($connection, $model, $qb = null)
    {
        return new static($connection, $model, $qb);
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
        $this->qb->addSelect($selects);
    }

    public function get($conditions = null)
    {
        $conditions = func_get_args();

        if (count($conditions) == 1):
            $value = reset($conditions);
            $conditions = [];
            $conditions[] = [$this->model->meta->primaryKey->name => $value];
        endif;

        return $this->_filterOrExclude($conditions);
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
    public function filter($conditions = null)
    {
        return $this->_filterOrExclude(func_get_args());
    }

    public function with($conditions = null)
    {
        return $this;
    }

    public function exclude($conditions = null)
    {
        return $this->_filterOrExclude(func_get_args());
    }

    public function exists() {
        if(!$this->_resultsCache):
            $instance = $this->_clone();
            $instance->qb->setMaxResults(1);
            $this->_resultsCache = $instance->execute();
        endif;

        return (bool) $this->_resultsCache;
    }

    public function update() {

    }

    public function _update($records) {
        return 1;
    }

    public function _filterOrExclude($conditions = null)
    {
        $instance = $this->_clone();

        //        $roles = $qb->select("*")
        //            ->from('testing_user')
        //            ->where("username =".$qb->createNamedParameter("df"))
        //            ->execute()
        //            ->fetchAll();

        Lookup::filters($instance->qb, $conditions);

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
        $this->qb->from($this->model->meta->dbTable);

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

        return $instance->qb->getSQL();
    }

    public function getRawSql() {
        $sql = $this->getSql();

        foreach ($this->qb->getParameters() as $key => $value) :
            $sql = str_replace(':'.$key, $this->connection->quote($value), $sql);
        endforeach;

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

            $this->_resultsCache = $this->mapResults($this->model, $this->execute());

            $this->_evaluated = true;
        endif;

        return $this->_resultsCache;
    }

    public function execute() {

        if(ArrayHelper::isEmpty(ArrayHelper::getValue($this->qb->getQueryParts(), 'select', null))):
            $this->qb->select('*');
        endif;

        if(ArrayHelper::isEmpty(ArrayHelper::getValue($this->qb->getQueryParts(), 'from', null))):
            $this->qb->from($this->model->meta->dbTable);
        endif;

       return $this->qb->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getQueryBuilder()
    {
        return $this->connection->createQueryBuilder();
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
        /** @var $newModel Model */
        $newModel = new $model->meta->modelName();
        $newModel->loadData($result);

        return $newModel;
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
        $this->connection->executeQuery($this->getSql())->rowCount();
    }

    /**
     * Evaluate the Queryset when a property is accessed from the Model Instance.
     *
     * @param $property
     * @ignore
     *
     * @return mixed
     */
    public function __get($property)
    {
        // check if queryset is already evaluated
        $this->getResults();

        return $this->_resultsCache->{$property};
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
        $qb = clone $this->qb;

        return self::createObject($this->connection, $this->model, $qb);
    }
}
