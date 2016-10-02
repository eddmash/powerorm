<?php
// only set if this has not been set incase we are developing outside codeigniter environment
use Eddmash\PowerOrm\BaseOrm;

if (!defined('ENVIRONMENT')):
    define('ENVIRONMENT', 'POWERORM_DEV');
endif;

// bootstrap the orm.
require_once 'bootstrap.php';

// if we are not on 'POWERORM_DEV' environment load the ci_instance
// since we are using the codeigniter.
// else create and instance of the orm.
if (ENVIRONMENT == 'POWERORM_DEV'):
    BaseOrm::consoleRun();
    BaseOrm::getDbConnection();
else:
    $base_dir = dirname(__FILE__);
    require_once $base_dir.'/application/libraries/powerorm/ci_instance.php';
endif;
