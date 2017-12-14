<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Db;

use Closure;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Eddmash\PhpGis\Db\Backends\Features\BaseFeatures;
use Eddmash\PhpGis\Db\Backends\Operations\OperationsInterface;

interface ConnectionInterface
{
    /**
     * Gets the parameters used during instantiation.
     *
     * @return array
     */
    public function getParams();

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string
     */
    public function getDatabase();

    /**
     * Gets the hostname of the currently connected database.
     *
     * @return string|null
     */
    public function getHost();

    /**
     * Gets the port of the currently connected database.
     *
     * @return mixed
     */
    public function getPort();

    /**
     * Gets the username used by this connection.
     *
     * @return string|null
     */
    public function getUsername();

    /**
     * Gets the password used by this connection.
     *
     * @return string|null
     */
    public function getPassword();

    /**
     * Gets the DBAL driver instance.
     *
     * @return \Doctrine\DBAL\Driver
     */
    public function getDriver();

    /**
     * Gets the ExpressionBuilder for the connection.
     *
     * @return \Doctrine\DBAL\Query\Expression\ExpressionBuilder
     */
    public function getExpressionBuilder();

    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public function getDatabasePlatform();

    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchemaManager();

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param \Closure $func the function to execute transactionally
     *
     * @throws \Exception
     */
    public function transactional(Closure $func);

    /**
     * Inserts a table row with specified data.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression the expression of the table to insert data into, quoted or unquoted
     * @param array  $data            an associative array containing column-value pairs
     * @param array  $types           types of the inserted data
     *
     * @return int the number of affected rows
     */
    public function insert($tableExpression, array $data, array $types = array());

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $query  the SQL query
     * @param array  $params the query parameters
     * @param array  $types  the parameter types
     *
     * @return int the number of affected rows
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function executeUpdate($query, array $params = array(), array $types = array());

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql    the SQL query
     * @param array  $params the query parameters
     * @param array  $types  the query parameter types
     *
     * @return array
     */
    public function fetchAll($sql, array $params = array(), $types = array());

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression the expression of the table on which to delete
     * @param array  $identifier      The deletion criteria. An associative array containing column-value pairs.
     * @param array  $types           the types of identifiers
     *
     * @return int the number of affected rows
     *
     * @throws InvalidArgumentException
     */
    public function delete($tableExpression, array $identifier, array $types = array());

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $statement the SQL query
     * @param array  $params    the query parameters
     * @param array  $types     the query parameter types
     *
     * @return array
     */
    public function fetchAssoc($statement, array $params = array(), array $types = array());

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string $statement the SQL query to be executed
     * @param array  $params    the prepared statement params
     * @param array  $types     the query parameter types
     *
     * @return array
     */
    public function fetchArray($statement, array $params = array(), array $types = array());

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $statement the SQL query to be executed
     * @param array  $params    the prepared statement params
     * @param int    $column    the 0-indexed column number to retrieve
     * @param array  $types     the query parameter types
     *
     * @return mixed
     */
    public function fetchColumn($statement, array $params = array(), $column = 0, array $types = array());

    /**
     * Gets the Configuration used by the Connection.
     *
     * @return \Doctrine\DBAL\Configuration
     */
    public function getConfiguration();

    /**
     * @return OperationsInterface
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getOperations();

    /**
     * @return BaseFeatures
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFeatures();
}
