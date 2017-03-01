
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

- **databaseConfigs**
    This are the database configurations the ORM will use to connect to a database.

    The orm uses `Doctrine Dbal <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_ to
    connect to database.

    This means the orm will support most if not all the databases suported by Doctrine dbal.

    To learn more about the database drivers supported and the different configurations requried for each databases
    please view
    `Database Configs <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html>`_

    An example of database configuration for mysql is presented below.

- **migrationPath**

    This is location where the ORM will use to store migrations files. in Codeigniter 3, this
    defaults to the ``APPPATH/migrations`` folder.

- **modelsPath**

    This is location where the ORM will exptect to find the model files. in Codeigniter 3, this
    defaults to the ``APPPATH/models`` folder.

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

A sample configuration file. ``config/orm.php``

.. code-block:: php

    $config['databaseConfigs']= [
        'dbname' => 'tester',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ];

    $config['migrationPath']= sprintf("%smigrations%s", APPPATH, DIRECTORY_SEPARATOR);;
    $config['modelsPath']= sprintf("%smodels%s", APPPATH, DIRECTORY_SEPARATOR);
    $config['dbPrefix']= 'testing_';
    $config['charset']= config_item('charset');