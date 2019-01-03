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

use Eddmash\PowerOrm\Exception\TypeError;

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
     * @param array $fields     the fields to select, if null all fields in the
     *                          model are selected
     * @param bool  $valuesOnly if true return
     * @param bool  $flat       if true returns the results as one array others
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
}
