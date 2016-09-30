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

define('NOT_PROVIDED', 'NOT_PROVIDED');

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
    public $registryCache;

    public $migrationPath;

    public $modelsPath;

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

    public static function getDbConnection()
    {

        if(static::$connection == null):
            $config = new Configuration();

            $connectionParams = array(
                'dbname' => 'tester',
                'user' => 'root',
                'password' => 'root1.',
                'host' => 'localhost',
                'driver' => 'pdo_mysql',
            );

            static::$connection = DriverManager::getConnection($connectionParams, $config);
        endif;

        return static::$connection;
    }

    /**
     * Configures an object with the initial property values.
     *
     * @param object $object     the object to be configured
     * @param array  $properties the property initial values given in terms of name-value pairs
     *
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) :
            $object->$name = $value;
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
