#############################
Model
#############################

.. contents::
    :local:
    :depth: 2

A model is the single, definitive source of information about your data. It contains the essential fields and behaviors
of the data you're storing. Generally, each model maps to a single database table.

A model is a PHP class that subclasses ``PModel`` or ``Eddmash\PowerOrm\Model\Model``.

Quick example
================

This example model defines a User, which has a firstName and lastName:

.. code-block:: php

     class User extends PModel
     {

         private function unboundFields(){
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
      See  :ref:`Table names for more details<table-names>`.

    - An id field is added automatically, but this behavior can be overridden.
      See :ref:`Automatic primary key fields <automatic-primary-key-fields>`.

    - The CREATE TABLE SQL in this example is formatted using PostgreSQL syntax, but it's worth noting PowerOrm uses
      SQL tailored to the database backend specified in your configurations file.

.. _automatic-primary-key-fields:

Automatic primary key fields
===============================
By default, PowerOrm gives each model the following field:

.. code-block:: php

   id = PModel::AutoField(['primaryKey'=>true])

This is an auto-incrementing primary key.

If you'd like to specify a custom primary key, just specify ``primarykey=true`` on one of your fields.
If PowerOrm sees you've explicitly set Field->primaryKey, it won't add the automatic id column.

Each model requires exactly one field to have ``primaryKey=true`` (either explicitly declared or automatically added).

.. _verbose-field-names:

Verbose field names
=====================
All fields accept a verboseName argument.

If the verbose name isn't given, PowerOrm will automatically create it using the field's attribute name,
converting underscores to spaces.

In this example, the verbose name is "person's first name":

.. code-block:: php

    first_name = PModel::CharField(['verboseName'=>"person's first name", 'maxLength'=30])

In this example, the verbose name is "first name":

.. code-block:: php

    first_name = PModel::CharField(['maxLength'=30])

Relationships
=================
The power of relational databases lies in relating tables to each other. PowerOrm offers ways to define the three most
common types of database relationships: many-to-one, many-to-many and one-to-one.

In all the relationships types a :ref:`recursive relationship <recursive_relation>` can be defined.

Many-to-one relationships
----------------------------
To define a many-to-one relationship, use PModel::ForeignKey. You use it just like any other Field type: by including
it on the ``unboundFields`` method of your model.

ForeignKey requires a 'to' argument: the class to which the model is related.

For example, if a Car model has a Manufacturer – that is,
a Manufacturer makes multiple cars but each Car only has one Manufacturer – use the following definitions:


.. code-block:: php

    class Car extends PModel{
        private function unboundFields()
        {
            return [
                'manufacturer' => PModel::ForeignKey(['to' => 'Manufacturer'])
            ];
        }
    }

    class Manufacturer extends PModel
    {

        private function unboundFields(){
            return [];
        }
    }


It's suggested, but not required, that the name of a ForeignKey field (manufacturer in the example above) be the name
of the model, lowercase.You can, of course, call the field whatever you want.

Many-to-many relationships
-----------------------------------
To define a many-to-many relationship, use ManyToManyField. You use it just like any other Field type: by including it
on the ``unboundFields`` method of your model.

ManyToManyField requires a 'to' argument: the class to which the model is related.

For example, if a Pizza has multiple Topping objects – that is, a Topping can be on multiple pizzas and each Pizza has
multiple toppings – here's how you'd represent that:

.. code-block:: php

    class Topping extends PModel
    {

        private function unboundFields(){
            return [
                'name'=> PModel::CharField(['maxLength'=>50])
            ];
        }
    }

    class Pizza extends PModel{
        private function unboundFields()
        {
            return [
                'toppings' => PModel::ManyToManyField(['to' => 'Topping'])
            ];
        }
    }

It's suggested, but not required, that the name of a ManyToManyField (toppings in the example above) be a plural
describing the set of related model objects.

It doesn't matter which model has the ManyToManyField, but you should only put it in one of the models – not both.

Generally, ManyToManyField instances should go in the object that's going to be edited on a form. In the above example,
toppings is in Pizza (rather than Topping having a pizzas ManyToManyField ) because it's more natural to think about a
pizza having toppings than a topping being on multiple pizzas. The way it's set up above, the Pizza form would let users
select the toppings.

Extra fields on many-to-many relationships
---------------------------------------------
When you're only dealing with simple many-to-many relationships such as mixing and matching pizzas and toppings,
a standard ManyToManyField is all you need. However, sometimes you may need to associate data with the relationship
between two models.

For example, consider the case of an application tracking the musical groups which musicians belong to. There is a 
many-to-many relationship between a person and the groups of which they are a member, so you could use a ManyToManyField 
to represent this relationship. However, there is a lot of detail about the membership that you might want to collect, 
such as the date at which the person joined the group.

For these situations, PowerOrm allows you to specify the model that will be used to govern the many-to-many
relationship. You can then put extra fields on the intermediate model. The intermediate model is associated with the
ManyToManyField using the through argument to point to the model that will act as an intermediary.
For our musician example, the code would look something like this:

.. code-block:: php

    class Person extends PModel
    {

        private function unboundFields(){
            return [
                'name'=> PModel::CharField(['maxLength'=>50])
            ];
        }
    }

    class Group extends PModel{
        private function unboundFields()
        {
            return [
                'name'=> PModel::CharField(['maxLength'=>50]),
                'members' => PModel::ManyToManyField(['to' => 'Person', 'through'=>'Membership'])
            ];
        }
    }

    class Membership extends PModel{
        private function unboundFields()
        {
            return [
                'person' => PModel::ForeignKey(['to' => 'Person']),
                'group' => PModel::ForeignKey(['to' => 'Group']),
                'invite_reason'=>PModel::CharField(['maxLength'=>65])
            ];
        }
    }

One-to-one relationships
---------------------------
To define a one-to-one relationship, use OneToOneField. You use it just like any other Field type: by including it
on the ``unboundFields`` method of your model.

This is most useful on the primary key of an object when that object "extends" another object in some way.

OneToOneField requires a 'to' argument: the class to which the model is related.

For example, if you were building a database of "places", you would build pretty standard stuff such as address,
phone number, etc. in the database. Then, if you wanted to build a database of restaurants on top of the places,
instead of repeating yourself and replicating those fields in the Restaurant model, you could make Restaurant have a
OneToOneField to Place (because a restaurant "is a" place; in fact, to handle this you'd typically use inheritance,
which involves an implicit one-to-one relation).


Meta Settings
===============
Give your model metadata by return an array of model meta setting from the method ``getMetaSettings``, like so:

.. code-block:: php

     class User extends PModel
     {

        private function unboundFields(){
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