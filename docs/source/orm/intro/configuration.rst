#############
Configuration
#############

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

- **charset**

    The charset used when working with strings.

- **timezone**

    Default: uses timezone of the current php installation.

    A string representing the time zone .

- **dbPrefix**

    This is the prefix to use in all tables created by the ORM.
    e.g.

    if ::

        dbPrefix = 'testing'

    all tables created will prefixed with testing so instead of the table *user* it will becomes *testing_user*.

.. _config_components:

- **components**

    This configuration serves two purposes:
     - Registering :ref:`applications<component_apps>`. This are
       applications/project
       that the orm will be used to manage models, migrations and perform
       queries.
     - Registering :ref:`components<component_home>` that extend the orm good
       examples of this are :

        - :ref:`Faker <faker_home>` which is used to generate dummy data for
          the orm.
        - :ref:`Debuging Toolbar <debugbar_home>` A tool bar to help in
          development to view things like what sql the orm ran.
        - :ref:`PhpGis <gis_home>` Makes the orm work with gis data.

    see :doc:`Components<components>` for more.


Sample Configuration file.
============================

A sample configurations.

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
            PhpGis::class,
            Toolbar::class,
            Faker::class,
        ],
        'signalManager' => function (BaseOrm $orm) {

            return new SignalManager();
        },
    ];