<?php

/**
 * Created by Eddilbert Macharia (edd.cowan@gmail.com)<http://eddmash.com>
 * Date: 10/14/16.
 */
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
        'autoload' => true,
    ],
    'dbPrefix' => 'demo_',
    'charset' => 'utf-8',
];
