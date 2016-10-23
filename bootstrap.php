<?php
/**
 * Bootstrap the application.
 */
use Eddmash\PowerOrm\Autoloader\Autoloader;
use Eddmash\PowerOrm\Config\OrmConfig;

define('POWERORM_VERSION', '1.1.0-pre-alpha');

$baseDir = isset($baseDir) ? $baseDir : __DIR__;

define('HOMEPATH', $baseDir);

// if ENVIRONMENT is not set and our base dir is 'powerorm' we might be running test
// otherwise we migh be on a codeigniter environmnent so create a codeigniter instance
if (!defined('ENVIRONMENT')):
    if (strtolower(basename(HOMEPATH)) === 'powerorm'):
        define('ENVIRONMENT', 'POWERORM_TESTING');
    else:
        $base_dir = HOMEPATH;
        require 'CiSetup.php';
    endif;
endif;

if (!defined('BASEPATH')) :
    define('BASEPATH', __DIR__.DIRECTORY_SEPARATOR);
    define('APPPATH', BASEPATH);
    define('POWERORM_BASEPATH', BASEPATH);
    define('POWERORM_SRCPATH', BASEPATH.'src'.DIRECTORY_SEPARATOR);
else :
    define('POWERORM_BASEPATH', APPPATH.'libraries/powerorm'.DIRECTORY_SEPARATOR);
    define('POWERORM_SRCPATH', APPPATH.'libraries/powerorm/src'.DIRECTORY_SEPARATOR);
endif;

$autoLoadFile = HOMEPATH.DIRECTORY_SEPARATOR.'vendor/autoload.php';

if (file_exists($autoLoadFile)):
    require $autoLoadFile;

endif;

require_once POWERORM_SRCPATH.'Autoloader/Autoloader.php';
require POWERORM_SRCPATH.'Autoloader/Config/BaseConfig.php';
require_once POWERORM_SRCPATH.'Autoloader/Config/OrmConfig.php';

// setup Autoloader
$loader = new Autoloader();
$loader->initialize(new OrmConfig());
$loader->register();
