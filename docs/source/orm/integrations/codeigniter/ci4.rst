Integrating with Codeigniter 4
==============================

This is recipe for using Powerorm with codeigniter. make sure to install powerorm via composer.
``composer require eddmash/powerorm:@dev``

.. contents::
   :local:
   :depth: 2

Codeigniter  4
--------------

.. note::

    Before its officially released ensure you have latest version as per github commits

For Codeigniter 4 and any other projects that use namespace(see :doc:`Laravel <../laravel>`)
you just need to ensure the orm is loaded early enough.

To intergrate the orm into CI4, It boils down to the following steps:

- :ref:`create config file <ci4_create_config_file>`
- :ref:`create a service <ci4_create_service>`
- :ref:`Load Orm <ci4_load_orm>`
- :ref:`Create Powerorm Command line <ci4_create_orm_cli>`

.. _ci4_create_config_file:

Create config file
..................

Powerorm needs some :doc:`configurations <../../intro/configuration>` for it to work like the database settings.

Create the :doc:`config <../../intro/configuration>` file for the orm ``application/Config/Powerorm.php``.


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


.. _ci4_create_service:

Create Service
..............

We need to create an orm service which we can use to access the orm across the application. If an instance does not
exist one will be created. We use a `getSharedInstance` to always get the same instance of the orm.

Add this method to the Service class at `application/Config/Services.php`

.. code-block:: php

    /**
     * @param bool $getShared
     * @return \Eddmash\PowerOrm\BaseOrm
     */
    public static function orm($getShared = true)
    {
        if ($getShared):
            return self::getSharedInstance('orm');
        endif;

        return \Eddmash\PowerOrm\Application::webRun(\Config\Powerorm::asArray());
    }

.. _ci4_load_orm:

Load the Orm
............

To load the orm we listen for the **pre_system** and call the orm service. This is a shared service hence we only get
the same instance of the orm through out the application.

Add this to `application/Config/Events.php`

.. code-block:: php

    Events::on('pre_system', function (){
        Services::orm();
    });


.. _ci4_create_orm_cli:

Create Powerorm Command File
............................

To be able to run :doc:`commands <../../ref/commands>` provided by powerorm, we need to create a codeigniter 4
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

With that you can run all the :doc:`commands <../../ref/commands>` that powerorm provides as follows:

.. code-block:: php

    php spark powerorm:pmanager
    php spark powerorm:pmanager makemigrations
    php spark powerorm:pmanager makemigrations --dry-run
    php spark powerorm:pmanager makemigrations --dry-run -vvv
    php spark powerorm:pmanager makemigrations -h
    php spark powerorm:pmanager migrate
    php spark powerorm:pmanager migrate zero
    php spark powerorm:pmanager robot

See :doc:`commands <../../ref/commands>` for all the availabel commands.
