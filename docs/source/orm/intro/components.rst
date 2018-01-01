##########
Components
##########

.. contents::
    :local:
    :depth: 4

Basics
------
.. _component_home:

A component in powerorm is any project that needs to extend the orm.

Basically its a class that
implements **Eddmash\\PowerOrm\\Components\\Component** .


This a the component class for the :doc:`Faker<../../faker/index>` library.

This library extends the orm by adding a **generatedata** command which is used
to generate dummy data.

.. code-block:: php


    namespace Eddmash\PowerOrmFaker;


    use Eddmash\PowerOrm\BaseOrm;
    use Eddmash\PowerOrm\Components\Component;
    use Eddmash\PowerOrmFaker\Commands\Generatedata;

    class Faker extends Component
    {

        function ready(BaseOrm $baseOrm)
        {
        }

        /**
         * Command classes
         * @return array
         * @since 1.1.0
         *
         * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
         */
        function getCommands()
        {
            return [
                Generatedata::class
            ];
        }
    }

Project Application
-------------------
.. _component_apps:

An application in powerorm is a form of a :ref:`component <component_home>` with
the difference being an application provides more information that determines
things like where

    - the orm should look for models,
    - where it should place the generated migrations

For a php project to use powerorm, an application class needs to be created for
that project.

If you have a project with the namespace App; Create a class the extends the
**Eddmash\\PowerOrm\\Components\\Application**.
This class should be placed on the same level as your models, migration folders.

.. code-block:: php

    namespace App;


    use Eddmash\PowerOrm\BaseOrm;
    use Eddmash\PowerOrm\Components\Application;

    class App extends Application
    {

        public function ready(BaseOrm $baseOrm)
        {
        }

    }

Technically this file can be placed anywhere on your project tree, To get this
flexibility you need to override :

    - :ref:`Application::getMigrationsPath()<application_getMigrationsPath>`
      to tell the the orm where to find the models files and

    - :ref:`Application::getMigrationsPath()<application_getMigrationsPath>`
      to tell the orm where to place generated migrations files.


Class Reference
---------------

Component
*********

.. php:class:: \\Eddmash\\PowerOrm\\Components\\Component

    .. php:method:: ready()

        .. _component_ready:

	    This method is invoked after the orm registry is ready .
	    This means the models can be accessed within this model without any
	    issues.

    .. php:method:: isQueryable()

        true if it this component is accessible as an attribute of the orm.


    .. php:method:: getInstance()

        Instance to return if the component is queryable..


    .. php:method:: getCommands()

        An array of Command classes that this component provides.


    .. php:method:: getName()

        Name to use when querying this component, ensure its unique.


Application
***********

.. php:class:: \\Eddmash\\PowerOrm\\Components\\Application

    .. php:method:: ready()

        .. _application_ready:

	    This method is invoked after the orm registry is ready .
	    This means the models can be accessed within this model without any
	    issues.

    .. php:method:: getMigrationsPath()

        .. _application_getMigrationsPath:

        This is location where the ORM will use to store migrations files.

    .. php:method:: getModelsPath()

        .. _application_getModelsPath:

        This is location where the ORM will expect to find the model files.


    .. php:method:: getDbPrefix()

        .. _application_getDbPrefix:

        This is the prefix to use in all tables created by the ORM for this
        project.e.g.
        if ::

            dbPrefix = 'testing'

        all tables created for this project will prefixed with testing so
        instead of the table *user* it will becomes *testing_user*.
