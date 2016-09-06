<?php
/**
 * Bootstrap the application.
 */
require 'vendor/autoload.php';

if(!defined('BASEPATH')):

    define('ENVIRONMENT', 'testing');
    define('BASEPATH', __DIR__.DIRECTORY_SEPARATOR);
    define('APPPATH', BASEPATH);
    define('POWERORM_BASEPATH', BASEPATH.'src'.DIRECTORY_SEPARATOR);

    require_once POWERORM_BASEPATH.'autoloader/Autoloader.php';
    require POWERORM_BASEPATH.'autoloader/BaseConfig.php';
    require_once POWERORM_BASEPATH.'autoloader/OrmConfig.php';

else:
    define('POWERORM_BASEPATH', APPPATH.'libraries/powerorm/src'.DIRECTORY_SEPARATOR);

    require_once POWERORM_BASEPATH.'autoloader/Autoloader.php';
    require POWERORM_BASEPATH.'autoloader/BaseConfig.php';
    require_once POWERORM_BASEPATH.'autoloader/OrmConfig.php';
endif;

// setup autoloader
$loader = new \CodeIgniter\Autoloader\Autoloader();
$loader->initialize(new \Config\OrmConfig());
$loader->register();

// create an instance of the ORM if we are in testing environment
// otherwise the instance is created by the framework
if(ENVIRONMENT == 'testing'):
    require_once 'Orm.php';
    new Orm();
endif;
