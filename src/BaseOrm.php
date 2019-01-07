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
use Doctrine\DBAL\DriverManager;
use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Checks\ChecksRegistry;
use Eddmash\PowerOrm\Checks\Tags;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Components\ComponentInterface;
use Eddmash\PowerOrm\Exception\AppRegistryNotReady;
use Eddmash\PowerOrm\Exception\ComponentException;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Signals\SignalManagerInterface;

define('NOT_PROVIDED', 'POWERORM_NOT_PROVIDED');

/**
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BaseOrm extends BaseObject
{
    const RECURSIVE_RELATIONSHIP_CONSTANT = 'this';

    private static $checkRegistry;

    /**
     * @var static
     */
    public static $instance;

    public static $SET_NULL = 'set_null';

    public static $CASCADE = 'cascade';

    public static $PROTECT = 'protect';

    public static $SET_DEFAULT = 'set_default';

    private $componentsloaded = false;

    /**
     * @var ComponentInterface[]
     */
    private $components = [];

    /**
     * @var Registry
     */
    private $registryCache;

    /**
     * Namespace used in migration.
     *
     * @internal
     *
     * @var string
     */
    private static $fakeNamespace = 'Eddmash\PowerOrm\__Fake';

    /**
     * @var ConnectionInterface
     */
    private static $connection;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param array $config
     * @ignore
     *
     * @throws Exception\KeyError
     */
    private function __construct(Settings $settings)
    {
        static::$connection = null;
        $this->settings = $settings;
        // setup the registry
        $this->registryCache = Registry::createObject();
    }

    public static function getCharset()
    {
        return self::getInstance()->getSettings()->getCharset();
    }

    //********************************** ORM Registry*********************************

    /**
     * @param array $configs
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function setup(Settings $settings, ConnectionInterface $connection = null)
    {
        $instance = static::getInstance($settings);
        $instance::$connection = $connection;
        $instance->loadComponents();
        $instance->loadRegistry();
        $instance->registerModelChecks();
        $instance->componentsReady();

        return $instance;
    }

    /**
     * Returns the numeric version of the orm.
     *
     * @return string
     */
    public function getVersion()
    {
        if (defined('POWERORM_VERSION')) {
            return POWERORM_VERSION;
        }
    }

    /**
     * @return ConnectionInterface
     *
     * @throws OrmException
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDatabaseConnection()
    {
        if (null == static::$connection) {
            $config = new Configuration();
            $db = $this->getSettings()->getDatabase();
            $db['wrapperClass'] = \Eddmash\PowerOrm\Backends\Connection::class;
            $this->getSettings()->setDatabase($db);
            try {
                static::$connection = DriverManager::getConnection(
                    $this->getSettings()->getDatabase(),
                    $config
                );
            } catch (\Exception $exception) {
                throw new OrmException($exception->getMessage());
            }
        }

        if (!static::$connection && empty($this->getSettings()->getDatabase())) {
            $message = 'The database configuration have not ' .
                'been provided, consult documentation for options';

            throw new OrmException($message);
        }

        return static::$connection;
    }

    /**
     * Returns the application registry. This method populates the registry the first time its invoked and caches
     * it since
     * its a very expensive method. subsequent calls get the cached registry.
     *
     * @return Registry
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function &getRegistry($load = false)
    {
        $orm = static::getInstance();

        if ($load) {
            $orm->loadRegistry();
        }

        return $orm->registryCache;
    }

    /**
     * This is just a shortcut method. get the current instance of the orm.
     *
     * @return BaseOrm
     */
    public static function &getInstance(Settings $settings = null)
    {
        if (null == static::$instance) {
            static::$instance = new static($settings);
        }

        return static::$instance;
    }

    /**
     * @return ConnectionInterface
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws OrmException
     */
    public static function getDbConnection()
    {
        return self::getInstance()->getDatabaseConnection();
    }

    /**
     * Returns the prefix to use on database tables.
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getDbPrefix()
    {
        return self::getInstance()->getSettings()->getDbPrefix();
    }

    /**
     * Runs checks on the application models.
     *
     * @internal
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function registerModelChecks()
    {
        $models = self::getRegistry()->getModels();

        /** @var $modelObj Model */
        foreach ($models as $name => $modelObj) {
            if (!$modelObj->hasMethod('checks')) {
                continue;
            }

            self::getCheckRegistry()->register([$modelObj, 'checks'], [Tags::Model]);
        }
    }

    /**
     * @param bool|false $recreate
     *
     * @return ChecksRegistry
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getCheckRegistry($recreate = false)
    {
        if (null === self::$checkRegistry || ($recreate && null !== self::$checkRegistry)) {
            self::$checkRegistry = ChecksRegistry::createObject();
        }

        return self::$checkRegistry;
    }

    public static function getQueryBuilder()
    {
        return self::getDbConnection()->createQueryBuilder();
    }

    public static function signalDispatch($signal, $object = null)
    {
        self::getInstance()->dispatchSignal($signal, $object);
    }

    // ---------------------------- ORM SETUP METHODS ----------------------

    /**
     * Populate the registray.
     */
    private function loadRegistry()
    {
        try {
            $this->registryCache->isAppReady();
        } catch (AppRegistryNotReady $e) {
            $this->registryCache->populate();
        }
    }

    /**
     * Load the components to the orm.
     */
    private function loadComponents()
    {
        if (!$this->componentsloaded) {
            foreach ($this->getSettings()->getComponents() as $componentClass) {
                $component = new $componentClass();
                if ($component instanceof ComponentInterface) {
                    static::getInstance()->addComponent($component);
                }
            }
            $this->componentsloaded = true;
        }
    }

    /**
     * Run the ready method on each of the components.
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    private function componentsReady()
    {
        self::getRegistry()->isAppReady();

        if ($this->componentsloaded) {
            foreach ($this->components as $component) {
                $component->ready($this);
            }
        }
    }

    private function addComponent(ComponentInterface $component)
    {
        if ($component->isQueryable()) {
            $this->components[$component->getName()] = $component;
        } else {
            $this->components[] = $component;
        }
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return ($this->getSettings()->getTimezone()) ?
            $this->getSettings()->getTimezone() :
            date_default_timezone_get();
    }

    /**
     * @param $name
     *
     * @return mixed
     *
     * @throws AppRegistryNotReady
     * @throws Exception\KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function __get($name)
    {
        $this->registryCache->isAppReady();

        if (ArrayHelper::hasKey($this->components, $name)) {
            return $this->components[$name]->getInstance();
        }

        return $this->{$name};
    }

    /**
     * @return SignalManagerInterface
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getSignalManager()
    {
        static $manager;
        if (is_callable($this->getSettings()->getSignalManager()) && null == $manager) {
            $manager = call_user_func($this->getSettings()->getSignalManager(), $this);
        }

        return $manager;
    }

    public static function isCheckSilenced($id)
    {
        return in_array($id, static::getInstance()->getSettings()->getSilencedChecks());
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return ComponentInterface[]
     */
    public function getComponents(bool $appsOnly = false)
    {
        if ($appsOnly) {
            $comps = array_filter($this->components, function ($component) {
                return $component instanceof AppInterface;
            });
            return $comps;
        }
        return $this->components;
    }

    /**
     * @param $name
     *
     * @return \Eddmash\PowerOrm\Components\ComponentInterface
     *
     * @throws \Eddmash\PowerOrm\Exception\ComponentException
     */
    public function getComponent($name)
    {
        foreach ($this->getComponents() as $component) {
            if ($component->getName() == $name) {
                return $component;
            }
        }
        throw new ComponentException(
            sprintf("'%s' is not a registered component", $name)
        );
    }

    public function setConnection(ConnectionInterface $connection)
    {
        static::$connection = $connection;
    }
}
