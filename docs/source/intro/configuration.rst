
##################################
 Powerorm On Codeigniter 3
##################################

Loading
==================

Load the PowerOrm library. preferable on autoload.::


$autoload['libraries'] = array('powerorm/orm',);

on the ``APPPATH/config/autoload.php``.

Configuration
========================
The ORM takes several configurations

- **database**
    This are the database configurations the ORM will use to connect to a database.

    The orm uses `Doctrine Dbal <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_ to
    connect to database.

    This means the orm will support most if not all the databases suported by Doctrine dbal.

    To learn more about the database drivers supported and the different configurations requried for each databases
    please view
    `Database Configs <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html>`_

    An example of database configuration for mysql is presented below.

- **migrations**

    This is location where the ORM will use to store migrations files. in Codeigniter 3, this
    defaults to the ``APPPATH/migrations`` folder.

- **models**

    This are configarations that relate to the models the orm will interact with.

    - path (*required*)

        This is location where the ORM will expect to find the model files.

    - namespace (*optional*)

        The namespace for the models in the path provided,
        if the models don't make use of namespace this optional is not needed.

    - autoload (*optional*)

        Tells the orm to autoload the models, if on projects that already
        take care of autoloading the models, set this option to false.

        **default:** true

- **dbPrefix**

    This is the prefix to use in all tables created by the ORM.
    e.g.

    if ::

        dbPrefix = 'testing'

    all tables created will prefixed with testing so instead of the table *user* it will becomes *testing_user*.

- **charset**

    The charset used when connecting to the database.

Sample Configuration file.
============================

A sample configurations.

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