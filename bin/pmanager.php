<?php
$base_dir = dirname(__FILE__);

require_once $base_dir."/application/libraries/powerorm/console/ci_instance.php";

require_once(APPPATH."libraries/powerorm/console/__init__.php");

require_once(APPPATH."libraries/powerorm/checks/__init__.php");

require_once(APPPATH."libraries/powerorm/migrations/__init__.php");

use powerorm\console\Manager;

Manager::run();