##############
Setup Powerorm
##############

.. contents::
    :local:
    :depth: 4

Install
-------
We assume your project structure looks like this ::

    app_base
        ----vendor/
        ----composer.json
        ----src
            ---- Models
            ---- Migrations


Via composer **(recommended)**::
    
	composer require eddmash/powerorm

Or add this to the composer.json file::

	"eddmash/powerorm": "1.1" (check latest version)

You could also Download or Clone package from github.

To rest of this guide assumes a plain php based project, no-frameworks used.

If you wish to see how to setup on specific frameworks please visit
:doc:`Frameworks Integrations <../integrations/index>`

Create configuration
--------------------
The orm requires a few
:doc:`Configurations<configuration>`, things like the database settings.

.. code-block:: php

    $config = [
        'database' => [
            'host' => '127.0.0.1',
            'dbname' => 'tester',
            'user' => 'admin',
            'password' => 'admin',
            'driver' => 'pdo_pgsql',
        ],
        'dbPrefix' => 'demo_',
        'charset' => 'utf-8',
    ];

Load the orm
------------

To load powerorm use the following code and pass the
:ref:`Configs <self_config>` needed for powerorm to work.

.. code-block:: php

    \Eddmash\PowerOrm\Loader::webRun($config);

This should be load as early as possible, Place it a the beginning of your
script.

Create application
------------------
So far we have not told the orm anything about our project, to do this
We need to create an :ref:`application class<component_apps>` the will be used
by the orm to get information about your php project.

If your project is namespaced as App;
Create a class the extends the ``Eddmash\\PowerOrm\\Components\\AppComponent``.
This class should be placed on the same level as your models, migration folders.

.. code-block:: php

    namespace App;

    use Eddmash\PowerOrm\BaseOrm;
    use Eddmash\PowerOrm\Components\AppComponent;

    class App extends AppComponent
    {

        public function ready(BaseOrm $baseOrm)
        {
        }

        public function getDbPrefix()
        {
            return "php_app";
        }
    }

Technically this file can be place anywhere in your project tree, To get this
flexibility you need to override :

    - :ref:`Application::getMigrationsPath()<application_getMigrationsPath>`
      to tell the the orm where to find the models files and

    - :ref:`Application::getMigrationsPath()<application_getMigrationsPath>`
      to tell the orm where to place generated migrations files.

Register Application
--------------------
Once we have the projects application class, we need to register it with the
orm.

To register we add the App class we have created above into our configurations
under the :ref:`component configuration<config_components>` as shown below.

.. code-block:: php

    $config = [
        'database' => [
            'host' => '127.0.0.1',
            'dbname' => 'tester',
            'user' => 'root',
            'password' => 'root1.',
            'driver' => 'pdo_mysql',
        ],
        'dbPrefix' => 'demo_',
        'charset' => 'utf-8',
        'timezone' => 'Africa/Nairobi',
        'components' => [
            App::class,
        ]
    ];

Create enable Powerorm on the command line
------------------------------------------
To be able to use the orm on the command line create a file on the same level as the **composer.json** file.
create file named **pmanager.php** and add the following .

.. code-block:: php

    use Eddmash\PowerOrm\Loader;
    require_once 'vendor/autoload.php';
    $configs = require_once 'src/config.php';
    Loader::consoleRun($configs);

See all the available commands :doc:`commands <../ref/commands>`.

With that you ready.

Enjoy !
