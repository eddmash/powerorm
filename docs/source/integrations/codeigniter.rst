Integrating with Codeigniter
============================

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

Powerorm needs some configurations for it to work like the database settings see :doc:`config <../intro/configuration>`

Create the :doc:`config <../intro/configuration>` file for the orm ``application/config/powerorm.php``

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

Powerorm comes packed with a set :doc:`commands <../ref/commands>` of console commands that help in managing your
models and the related database.

Since Codeigniter 3 does not have a CLI module like Codeigniter 4 does.

Copy the ``vendor/eddmash/powerorm/powerorm:pmanager.php`` file to you projects base directory
i.e. on the same level as vendor directory and index.php.

Then adjust the settings on the copied to those of your project.


Codeigniter  4
--------------

.. note::

    Before its officially released ensure you have latest version as per github commits

For Codeigniter 4 and any other projects that use namespace(see :doc:`Laravel <laravel>`)
you just need to ensure the orm is loaded early enough.

In Codeigniter 4 *(i'm still exploring codeigniter 4, but as of now)*
powerorm can be loaded at any one of the environment files under **application/Config/Boot/** .

    application/Config/Boot/development.php
    application/Config/Boot/production.php
    application/Config/Boot/testing.php

Depending on the environment in use add the following line at the bottom.

.. code-block:: php

    \Eddmash\PowerOrm\Application::webRun(\Config\Powerorm::asArray());

Create config file
..................

Powerorm needs some :doc:`configurations <../intro/configuration>` for it to work like the database settings.

Create the :doc:`config <../intro/configuration>` file for the orm ``application/Config/Powerorm.php``.


.. code-block:: php

    namespace Config;


    use CodeIgniter\Config\BaseConfig;

    class Powerorm extends BaseConfig
    {
        public static function asArray()
        {
            return [
                'database' => [
                    'host' => '127.0.0.1',
                    'dbname' => 'tester',
                    'user' => 'root',
                    'password' => '',
                    'driver' => 'pdo_mysql',
                ],
                'migrations' => [
                    'path' => sprintf('%sMigrations%s', APPPATH, DIRECTORY_SEPARATOR),
                ],
                'models' => [
                    'path' => sprintf('%sModels%s', APPPATH, DIRECTORY_SEPARATOR),
                    'namespace' => 'App\Models\\',
                ],
                'dbPrefix' => 'demo_',
                'charset' => 'utf-8',
            ];

        }
    }

Create Powerorm Command File
............................

To be able to run :doc:`commands <../ref/commands>` provided by powerorm, we need to create a codeigniter 4
command that will enable us interact with powerorm.

create the file ``application/Commands/Powerorm.php`` and add the following content.

.. code-block:: php

    namespace App\Commands;

    use CodeIgniter\CLI\BaseCommand;
    use Eddmash\PowerOrm\Console\Manager;
    use Symfony\Component\Console\Input\ArgvInput;

    class Powerorm extends BaseCommand
    {
        protected $group = 'Powerorm';
        protected $name  = 'powerorm:pmanager';
        protected $description = 'Displays powerorm commands.';


        public function run(array $params)
        {
            // remove the 'ci4.php' from the arguments
            $input = new ArgvInput(array_slice($_SERVER['argv'], 1));

            // launch powerorm console
            Manager::run(true, $input);
        }
    }

With that you can run all the :doc:`commands <../ref/commands>` that powerorm provides as follows:

.. code-block:: php

    php ci.php powerorm:pmanager
    php ci.php powerorm:pmanager makemigrations
    php ci.php powerorm:pmanager makemigrations --dry-run
    php ci.php powerorm:pmanager makemigrations --dry-run -vvv
    php ci.php powerorm:pmanager makemigrations -h
    php ci.php powerorm:pmanager migrate
    php ci.php powerorm:pmanager migrate zero
    php ci.php powerorm:pmanager robot

See :doc:`commands <../ref/commands>` for all the availabel commands.
