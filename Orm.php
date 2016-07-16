<?php
define('POWERORM_BASEPATH', APPPATH.'libraries/powerorm'.DIRECTORY_SEPARATOR);

require_once POWERORM_BASEPATH."autoloader/Autoloader.php";
require_once POWERORM_BASEPATH."autoloader/OrmConfig.php";

// setup auto loader
$loader = new \CodeIgniter\Autoloader\Autoloader();
$loader->initialize(new \Config\OrmConfig());
$loader->register();

use powerorm\BaseOrm;

/**
 * This class makes the orm available to codeigniter since the orm uses namespaces.
 * Class Orm
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Orm extends BaseOrm{


}