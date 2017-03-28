<?php

//if (strtolower(basename(__DIR__)) === 'powerorm'):
use Eddmash\PowerOrm\Application;

$composerPath = dirname(dirname(dirname(__FILE__))).'/autoload.php';

if (file_exists($composerPath)):
    require_once $composerPath;
endif;

// bootstrap the orm.
require_once 'bootstrap.php';
Application::consoleRun();
