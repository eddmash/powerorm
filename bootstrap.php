<?php
/**
 * Bootstrap the application.
 */
use Eddmash\PowerOrm\Autoloader\Autoloader;
use Eddmash\PowerOrm\Config\OrmConfig;

define('POWERORM_VERSION', '1.1.0');

if (!defined('BASEPATH')) :
    define('ENVIRONMENT', 'testing');
    define('BASEPATH', __DIR__.DIRECTORY_SEPARATOR);
    define('APPPATH', BASEPATH);
    define('POWERORM_BASEPATH', BASEPATH);
    define('POWERORM_SRCPATH', BASEPATH.'src'.DIRECTORY_SEPARATOR);
else :
    define('POWERORM_BASEPATH', APPPATH.'libraries/powerorm'.DIRECTORY_SEPARATOR);
    define('POWERORM_SRCPATH', APPPATH.'libraries/powerorm/src'.DIRECTORY_SEPARATOR);
endif;

$autoloadFile = POWERORM_BASEPATH.'vendor/autoload.php';
if(file_exists($autoloadFile)):
    require $autoloadFile;
endif;

require_once POWERORM_SRCPATH.'Autoloader/Autoloader.php';
require POWERORM_SRCPATH.'Autoloader/Config/BaseConfig.php';
require_once POWERORM_SRCPATH.'Autoloader/Config/OrmConfig.php';
// setup Autoloader
$loader = new Autoloader();
$loader->initialize(new OrmConfig());
$loader->register();

// create an instance of the ORM if we are in testing environment
// otherwise the instance is created by the framework
if (ENVIRONMENT == 'testing') {
    require_once 'Orm.php';
}
