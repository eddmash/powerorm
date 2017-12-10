Integrating with Codeigniter 3
==============================

This is recipe for using Powerorm with codeigniter. make sure to install powerorm via composer.
``composer require eddmash/powerorm:@dev``

.. contents::
   :local:
   :depth: 2


Codeigniter  3
--------------

.. note::

    This might not work for all CodeIgniter versions and may require
    slight adjustments.


Here is how to set it up:

Make a CodeIgniter library that is both a wrapper and a bootstrap
for Powerorm.CodeIgniter

Setting up the file structure
.............................

Here are the steps:

-  Add a php file to your application/libraries folder
   called Powerorm.php. This is going to be your wrapper/bootstrap for powerorm

-  Open your config/autoload.php file and autoload
   your Powerorm library.

.. code-block:: php

   $autoload['libraries'] = array('powerorm');

Creating your Powerorm CodeIgniter library
..........................................

Now, here is what your ``application/libraries/Powerorm.php`` file should look like.
Customize it to your needs.

.. code-block:: php

    class Powerorm
    {

        function __construct($config)
        {
            $autoLoader = require_once FCPATH.'vendor/autoload.php';
            $this->instance = \Eddmash\PowerOrm\Application::webRun($config, $autoLoader);
        }

    }

Create config file
..................

Powerorm needs some configurations for it to work like the database settings see :doc:`config <../../intro/configuration>`

Create the :doc:`config <../../intro/configuration>` file for the orm ``application/config/powerorm.php``

.. code-block:: php

    $config['database'] = [
        'host' => 'localhost',
        'dbname' => 'tester',
        'user' => 'root',
        'password' => '',
        'driver' => 'pdo_mysql',
    ];

    $config['migrations'] = [
        'path' => APPPATH.'migrations',
    ];
    $config['models'] = [
        'path' => APPPATH.'models',
        'namespace' => 'App\Models',
    ];

Copy powerorm console manager
.............................

Powerorm comes packed with a set :doc:`commands <../../ref/commands>` of console commands that help in managing your
models and the related database.

Since Codeigniter 3 does not have a CLI module like Codeigniter 4 does.

Copy the ``vendor/eddmash/powerorm/powerorm:pmanager.php`` file to you projects base directory
i.e. on the same level as vendor directory and index.php.

Then adjust the settings on the copied to those of your project.

See :doc:`commands <../../ref/commands>` for all the availabel commands.
