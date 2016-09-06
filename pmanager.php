<?php
$base_dir = dirname(__FILE__);

require_once $base_dir . "/application/libraries/powerorm/ci_instance.php";

use eddmash\powerorm\console\Manager;

Manager::run();
