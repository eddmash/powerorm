<?php

use Eddmash\PowerOrm\Application;

// ensure the classes are auto loaded
$composerLoader = require_once 'vendor/autoload.php';

// create in the database config
$config = [
    'database' => [
        'host' => '127.0.0.1',
        'dbname' => 'tester',
        'user' => 'admin',
        'password' => 'admin',
        'driver' => 'pdo_pgsql',
    ],
    'migrations' => [
        'path' => dirname(__FILE__).'/application/Migrations',
    ],
    'models' => [
        'path' => dirname(__FILE__).'/application/Models',
        'namespace' => 'App\Models',
    ],
    'dbPrefix' => 'demo_',
    'charset' => 'utf-8',
];

Application::consoleRun($config, $composerLoader);
