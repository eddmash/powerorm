<?php
/**
 * Bootstrap file for PHP UNIT TESTING.
 */

require 'vendor/autoload.php';

define('BASEPATH', __DIR__.DIRECTORY_SEPARATOR);
define('APPPATH', BASEPATH);
define('ENVIRONMENT', 'development');
define('POWERORM_BASEPATH', BASEPATH .'src' . DIRECTORY_SEPARATOR);

require_once POWERORM_BASEPATH . "autoloader/Autoloader.php";
require POWERORM_BASEPATH . 'autoloader/BaseConfig.php';
require_once POWERORM_BASEPATH . "autoloader/OrmConfig.php";

// setup auto loader
$loader = new \CodeIgniter\Autoloader\Autoloader();
$loader->initialize(new \Config\OrmConfig());
$loader->register();


require_once BASEPATH . "Orm.php";
new Orm();