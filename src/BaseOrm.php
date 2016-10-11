<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\Console\Manager;

define('NOT_PROVIDED', 'POWERORM_NOT_PROVIDED');

class BaseOrm extends Object
{
    private static $instance;
    public static $SET_NULL = 'set_null';
    public static $CASCADE = 'cascade';
    public static $PROTECT = 'protect';
    public static $SET_DEFAULT = 'set_default';

    /**
     * @var Registry
     */
    private $registryCache;

    /**
     * The configurations to use to connect to the database.
     *
     * It should be an array which must contain at least one of the following.
     *
     * Either 'driver' with one of the following values:
     *
     *     pdo_mysql
     *     pdo_sqlite
     *     pdo_pgsql
     *     pdo_oci (unstable)
     *     pdo_sqlsrv
     *     pdo_sqlsrv
     *     mysqli
     *     sqlanywhere
     *     sqlsrv
     *     ibm_db2 (unstable)
     *     drizzle_pdo_mysql
     *
     * OR 'driverClass' that contains the full class name (with namespace) of the
     * driver class to instantiate.
     *
     * Other (optional) parameters:
     *
     * <b>user (string)</b>:
     * The username to use when connecting.
     *
     * <b>password (string)</b>:
     * The password to use when connecting.
     *
     * <b>driverOptions (array)</b>:
     * Any additional driver-specific options for the driver. These are just passed
     * through to the driver.
     *
     * <b>pdo</b>:
     * You can pass an existing PDO instance through this parameter. The PDO
     * instance will be wrapped in a Doctrine\DBAL\Connection.
     *
     * <b>wrapperClass</b>:
     * You may specify a custom wrapper class through the 'wrapperClass'
     * parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     *
     * <b>driverClass</b>:
     * The driver class to use.
     *
     * <strong>USAGE:</strong>
     *
     * [
     *       'dbname' => 'tester',
     *       'user' => 'root',
     *       'password' => 'root1.',
     *       'host' => 'localhost',
     *       'driver' => 'pdo_mysql',
     * ]
     *
     * @var array
     */
    private $databaseConfigs = [
        'dbname' => 'tester',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ];

    /**
     * path from where to get and put migration files.
     *
     * @var string
     */
    private $migrationPath;

    /**
     * Path from where to get the models.
     *
     * @var string
     */
    private $modelsPath;

    /**
     * @var Connection
     */
    private static $connection;

    /**
     * @param array $config
     * @ignore
     */
    public function __construct($config = [])
    {
        self::configure($this, $config);

        // setup the registry
        $this->registryCache = Registry::createObject();
    }

    public static function getModelsPath()
    {
        return APPPATH.'models/';
    }

    public static function getMigrationsPath()
    {
        return APPPATH.'migrations/';
    }

    //********************************** ORM Registry*********************************

    /**
     * Returns the numeric version of the orm.
     *
     * @return string
     */
    public function getVersion()
    {
        if (defined('POWERORM_VERSION')):
            return POWERORM_VERSION;
        endif;
    }

    /**
     * @deprecated
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function version()
    {
        return $this->getVersion();
    }

    /**
     * Returns the application registry. This method populates the registry the first time its invoked and caches it since
     * its a very expensive method. subsequent calls get the cached registry.
     *
     * @return Registry
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getRegistry()
    {
        $orm = static::getInstance();

        if (!$orm->registryCache->isAppReady()):
            $orm->registryCache->populate();
        endif;

        return $orm->registryCache;
    }

    /**
     * This is just a shortcut method. get the current instance of the orm.
     *
     * @return BaseOrm
     */
    public static function &getInstance()
    {
        $instance = null;
        if(ENVIRONMENT == 'POWERORM_DEV'):
            $instance = static::_standAloneEnvironment();
        else:
            $instance = static::_ciEnvironment();
        endif;

        return $instance;
    }

    public static function _ciEnvironment() {
        $ci = static::getCiObject();
        $orm = &$ci->orm;

        return $orm;
    }

    public static function _standAloneEnvironment() {
        if(static::$instance == null):
            static::$instance = self::createObject();
        endif;

        return self::$instance;
    }

    /**
     * @return \CI_Controller
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function &getCiObject()
    {
        return get_instance();
    }

    /**
     * @return Connection
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getDbConnection()
    {
        return self::getInstance()->getDatabaseConnection();
    }

    /**
     * @return Connection
     * @throws \Doctrine\DBAL\DBALException
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDatabaseConnection()
    {
        if(static::$connection == null):
            $config = new Configuration();

            static::$connection = DriverManager::getConnection($this->databaseConfigs, $config);
        endif;

        return static::$connection;
    }

    /**
     * Configures an object with the initial property values.
     *
     * @param object $object     the object to be configured
     * @param array  $properties the property initial values given in terms of name-value pairs
     * @param array  $map        if set the the key should be a key on the $properties and the value should a a property on
     *                           the $object to which the the values of $properties will be assigned to
     *
     * @return object the object itself
     */
    public static function configure($object, $properties, $map = [])
    {
        foreach ($properties as $name => $value) :
            if(array_key_exists($name, $map)):
                $name = $map[$name];
            endif;

            if(property_exists($object, $name)):
                $object->$name = $value;
            endif;

        endforeach;

        return $object;
    }

    public static function createObject($config = []) {

        return new static($config);
    }

    public static function consoleRun($config = []) {
        static::createObject($config);
        Manager::run();
    }
}
