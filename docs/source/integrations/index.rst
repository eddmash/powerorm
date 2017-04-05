####################
Integrating Powerorm
####################


This is guide in setting up and using Powerorm on projects.

Setting up
----------

For projects that use namespace that are loaded using composer autoloader. Place powerorm in a locatin where it's
loaded early enough.

To load powerorm use the following code and pass the :ref:`Configs <self_config>` needed for powerorm to work

.. code-block:: php

    \Eddmash\PowerOrm\Application::webRun($config);


.. _self_config:

Configuration
-------------

Powerorm takes several configurations see :doc:`Configs <../intro/configuration>` for options.

.. code-block:: php

    $config = [
        'database' => [
            'host' => '127.0.0.1',
            'dbname' => 'tester',
            'user' => 'admin',
            'password' => 'admin',
            'driver' => 'pdo_pgsql',
        ],
        'migrations' => [
            'path' => dirname(__FILE__) . '/application/Migrations',
        ],
        'models' => [
            'path' => dirname(__FILE__) . '/application/Models',
            'namespace' => 'App\Models',
        ],
        'dbPrefix' => 'demo_',
        'charset' => 'utf-8',
    ];

Command Line
------------

To be able to use any of the command line command packaged with the orm e.g
commands to create migrations for models in the project.

Copy the ``vendor/eddmash/powerorm/pmanager.php`` file to you projects base directory
e.g. on the same level as vendor directory.

see below for setup on some common frameworks :

.. toctree::
    :titlesonly:

    codeigniter
    laravel