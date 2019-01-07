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

use Eddmash\PowerOrm\Exception\InvalidArgumentException;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;

interface QuerysetInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    public function get();

    public function filter();

    /**
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getResults();

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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getSql();

    /**
     * Returns the results as an array of associative array that represents a
     * record in the database.
     *
     * The orm does not try map the into  there  respective models.
     *
     * @param array $fields the fields to select, if null all fields in the
     *                          model are selected
     * @param bool $valuesOnly if true return
     * @param bool $flat if true returns the results as one array others
     *                          it returns results as array of arrays each
     *                          which represents a record in the database for the
     *                          selected field.
     *                          (only works when valueOnly is true)
     *
     * @return Queryset
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws TypeError
     */
    public function asArray($fields = [], $valuesOnly = false, $flat = false);

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement
     * executed by the corresponding object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement,
     * some databases may return the number of rows returned by that statement. However,
     * this behaviour is not guaranteed for all databases and should not be
     * relied on for portable applications.
     *
     * @internal
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function count();

    /**
     * Return a query set in which the returned objects have been annotated
     * with extra data or aggregations.
     *
     * @return Queryset
     *
     * @throws InvalidArgumentException
     * @throws ValueError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function annotate();

    /**
     * Returns a new QuerySet instance with the ordering changed.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @param array $fieldNames
     *
     * @return Queryset
     *
     * @throws \Eddmash\PowerOrm\Exception\FieldError
     */
    public function orderBy($fieldNames = []): self;

    /**
     * @param array $kwargs
     *
     * @return array
     *
     * @throws TypeError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function aggregate($kwargs = []): self;

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
    public function selectRelated(?array $fields): self;

    /**
     * Return a new QuerySet instance that will prefetch the specified
     * Many-To-One and Many-To-Many related objects when the QuerySet is evaluated.
     *
     * When prefetchRelated() is called more than once, append to the list of
     * prefetch lookups. If prefetch_related(None) is called, clear the list.
     *
     * @param array|null $lookups
     *
     * @return Queryset
     */
    public function prefetchRelated(?array $lookups): self;

    public function exclude();

    public function exists();

    /**
     * @param $offset
     * @param $size
     *
     * @return $this
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function limit($offset, $size);

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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function size();

    public function update();

    /**
     * Returns a new QuerySet that is a copy of the current one.
     *
     * This allows a QuerySet to proxy for a model manager in some cases.
     *
     * @return $this
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function all();
}
