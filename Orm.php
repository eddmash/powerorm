<?php
define('POWERORM_BASEPATH', APPPATH . 'libraries/powerorm/src' . DIRECTORY_SEPARATOR);

require_once POWERORM_BASEPATH . "autoloader/Autoloader.php";
require POWERORM_BASEPATH . 'autoloader/BaseConfig.php';
require_once POWERORM_BASEPATH . "autoloader/OrmConfig.php";

// setup auto loader
$loader = new \CodeIgniter\Autoloader\Autoloader();
$loader->initialize(new \Config\OrmConfig());
$loader->register();

use eddmash\powerorm\app\Registry;
use eddmash\powerorm\BaseOrm;
use eddmash\powerorm\db\Connection;

/**
 * This class makes the orm available to codeigniter since the orm uses namespaces.
 * Class Orm
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Orm extends BaseOrm
{

    /**
     * @var Registry
     */
    protected static $registry;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }


    /**
     * Returns the application registry.
     *
     * @return Registry
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function get_registry()
    {
        if (static::$registry == null):
            static::$registry = new Registry();
        endif;

        static::$registry->populate();

        return static::$registry;
    }


    /**
     * This is just a shortcut method. get the current instance of the orm
     * @return Orm
     */
    public static function &get_instance()
    {
        $ci =& self::ci_instance();
        $orm = &$ci->orm;
        return $orm;
    }


    /**
     * @return CI_Controller
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function &ci_instance()
    {
        return get_instance();
    }


    public static function dbconnection()
    {
        return Connection::instance();
    }
}
