<?php

$base_dir = dirname(__FILE__);

require_once $base_dir.'/application/libraries/powerorm/console/ci_instance.php';

use powerorm\console\Manager;

Manager::run();
