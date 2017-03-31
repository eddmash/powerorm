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
        'password' => 'ivamers_ktr_2016',
        'driver' => 'pdo_pgsql',
    ],
    'migrations' => [
        'namespace' => 'App\Migrations',
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
