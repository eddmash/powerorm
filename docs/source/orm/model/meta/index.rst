#############################
Model Meta Settings
#############################

This document explains all the possible metadata options that you can give your model in its ``getMetaSettings`` method.
see example below

.. contents::
   :local:
   :depth: 1

dbTable
=======

The name of the database table to use for the model::

    dbTable = 'inventory_user'

.. _table-names:

Table names
***********
To save you time, PowerOrm automatically derives the name of the database table from the name of your model class.

For example, if you have, a model defined as class Book will have a database table named `book`.

To override the database table name, use the ``dbTable`` parameter in ``getMetaSettings`` model method.

managed
========= 
**Default True**,

When managed is true PowerOrm will create the appropriate database tables in migrate or as part of migrations and
remove them as part of a flush management command. That is, PowerOrm manages the database tables' lifecycles.

If false, no database table creation or deletion operations will be performed for this model. This is useful if the
model represents an existing table or a database view that has been created by some other means. This is the only
difference when managed=false. All other aspects of model handling are exactly the same as normal.

This includes:

- Adding an automatic primary key field to the model if you don't declare it. To avoid confusion for later code readers,
  it's recommended to specify all the columns from the database table you are modeling when using unmanaged models.

- If a model with managed=false contains a ManyToManyField that points to another unmanaged model, then the
  intermediate table for the many-to-many join will also not be created. However, the intermediary table between one
  managed and one unmanaged model will be created.

  If you need to change this default behavior, create the intermediary table as an explicit model
  (with managed set as needed) and use the ManyToManyField **through** attribute to make the relation
  use your custom model. See  :ref:`Through model <through_model>`

If you are interested in changing the PHP-level behavior of a model class, you could set managed=false and create a copy
of an existing model. However, there's a better approach for that situation: :doc:`Proxy models <../proxy>`.

proxy
=====
**default false**

If proxy = true, a model which subclasses another model will be treated as a :doc:`Proxy models <../proxy>`.

verboseName
===========
A human-readable name for the object, singular::

    verboseName = "pizza"

If this is not given, PowerOrm will use a munged version of the class name: CamelCase becomes camel case.

Example
=======

.. code-block:: PHP

   use Eddmash\PowerOrm\Model\Model;

   class User extends Model
   {

       private function unboundFields()
       {
           return [
             'username'=> Model::CharField(['maxLength'=>25])
           ];
       }

       public function getMetaSettings()
       {
           return [
               'proxy'=>false,
               'managed'=>true,
               'verbose'=> "Local Users",
               'dbTable'=> 'demo_user'
           ];
       }
   }
