#############################
Model
#############################

.. contents::
    :local:
    :depth: 2

A model is the single, definitive source of information about your data. It contains the essential fields and behaviors
of the data you’re storing. Generally, each model maps to a single database table.

A model is a PHP class that subclasses ``PModel`` or ``Eddmash\PowerOrm\Model\Model``.

Quick example
================

This example model defines a User, which has a firstName and lastName:

.. code-block:: php

     class User extends PModel
     {

         public function unboundFields(){
             return [
                 'firstName'=>PModel::CharField(['maxLength'=>30]),
                 'lastName'=>PModel::CharField(['maxLength'=>30]),
             ];
         }
     }

firstName and lastName are fields of the model and each attribute maps to a database column.

Model Fields
==============
The most important part of a model – and the only required part of a model – is the list of database fields it defines.

Model fields are defined on ``unboundFields`` method which should return an associative array whose :

- **keys** are the names of the fields.Be careful not to choose field names that conflict with the models
  API like clean, save, or delete.

- **values** are instances of one of the subclass of the ``Eddmash\PowerOrm\Model\Field`` class.

All the field subclasses can be accessed from the PModel, via static methods whose name matches that of the subclass.
e.g to access the ``Eddmash\PowerOrm\Model\Field\CharField`` subclass from the PModel use the ``PModel::CharField()``.

PowerOrm uses the models fields to determine the database column type (e.g. INTEGER, VARCHAR).

PowerOrm ships with dozens of built in field types, you can find the complete list in the
:doc:`model field reference <field/index>`.

The above Person model would create a database table like this:

.. code-block:: sql

    CREATE TABLE user (
        "id" serial NOT NULL PRIMARY KEY,
        "firstName" varchar(30) NOT NULL,
        "lastName" varchar(30) NOT NULL
    );

.. note::
    - The name of the table, user, is automatically derived from some model metadata but can be overridden.
      See Table names for more details.

    - An id field is added automatically, but this behavior can be overridden.
      See Automatic primary key fields.

    - The CREATE TABLE SQL in this example is formatted using PostgreSQL syntax, but it’s worth noting PowerOrm uses
      SQL tailored to the database backend specified in your configurations file.

Meta Settings
===============
Give your model metadata by return an array of model meta setting from the method ``getMetaSettings``, like so:

.. code-block:: php

     class User extends PModel
     {

        public function unboundFields(){
            return [
                'firstName'=>PModel::CharField(['maxLength'=>30]),
                'lastName'=>PModel::CharField(['maxLength'=>30]),
            ];
        }

        public function getMetaSettings(){
            return [
                'dbTable'=>"local_user",
                'verboseName'=>"Local Users",
            ];
        }
     }

Model metadata is 'anything that's not a field' such as  database table name (db_table) or human-readable .
None are required, and overriding the getMetaSettings method is completely optional.

A complete list of all possible Meta options can be found in the :doc:`model option reference <meta/index>`.


.. toctree::
   :caption: More Model Information
   :titlesonly:

   inheritance
   multi_table_inheritance
   abstract
   proxy
   meta/index
   field/index