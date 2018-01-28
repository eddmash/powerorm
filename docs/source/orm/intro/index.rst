#######################
Introduction
#######################

Powerorm at a glance
-------------------------

This is an informal overview of how to write a database-driven Web app with
Powerorm.
The goal of this document is to give you enough technical specifics to
understand how Powerorm works.

This assumes you have already :doc:`Installed <install>` the orm

Design your model
----------------------
Powerorm is an object-relational mapper in which you describe your database
layout in PHP code.

The data-model syntax offers many rich ways of representing your models – so far.

What all this means is if an app requires a database table called role with two
columns name and code. Instead of creating this table manually on database
server. The orm can do this for us.

We tell the orm of our intentions by creating a :doc:`Model<../model/index>`
which outlines the table, columns to be on that table and anyother information
needed on the table.

For the table role outlined above, The model for that table would look as below

.. code-block:: php

    // application/models/Role.php

    use Eddmash\PowerOrm\Model\Model;

    class Role extends Model
    {
        private function unboundFields() {
            return [
                'name' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
                'code' => Model::CharField(['maxLength' => 10, 'dbIndex' => true]),
            ];
        }

    }


Create Table
--------------

So far we have created a model that represents a role table, but the actual
table does not exist yet.

.. note::

   The advantage of having the orm create tables for is that it will keep track
   of any changes made to the models, hence we can always undo or redo any
   changes we have made to the models reflected onto the database. This is made
   possible by :doc:`Migration<../migration/index>`

Now to create the table that represents the Role model on the database.

Run this command to have orm keep track of the models state by detecting any
changes made.

::

    $ php pmanager.php makemigrations

Run this :doc:`command<../ref/commands>` to have the orm apply the changes on
the database.

::

    $ php pmanager.php migrate


The migrate :doc:`command<../ref/commands>` looks at all your available models
and creates tables in your database for whichever tables don't already exist,
as well as optionally providing much richer schema control.

If your project is based on an framework
:doc:`Integrations <../integrations/index>` for how to access
Powerorm :doc:`command<../ref/commands>` line utility.

Persisting Objects to the Database
-------------------------------------

Now that you have mapped the Role model to its corresponding role table,
you're ready to persist Role objects to the database.

.. code-block:: php

    $role = new Role();
    $role->name = "test role";
    $role->code = "test_role";
    $role->save();
    var_dump("saved ".$role->id);


Fetching Objects from the Database
-------------------------------------

Fetching an object back out of the database is even easier.

When you query for a particular type of object, you always use a "manager".
You can think of a manager as a PHP class whose only job is to help you fetch
models of a certain class.
You can access the manager object for a model class via the objects() method:

.. code-block:: php

    // fetch select * from role
    $roles = Role::objects()->all();

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

    // fetch select * from role where code = 'Et qui qui'
    $roles = Role::objects()->filter(['code'=>"Et qui qui"]);

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

    // fetch SELECT * FROM testing_role WHERE (code = a) AND (code = qu)
    $roles = Role::objects()->filter(['code'=>"qu"], ["code"=>"a"]);

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

    // fetch SELECT * FROM testing_role WHERE (code LIKE %v) AND (code LIKE e%)
    $roles = Role::objects()->filter(['code__startswith'=>"e", "code__endswith"=>"v"]);

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

    // fetch SELECT * FROM testing_role WHERE (code LIKE a%) OR (code LIKE %qu)
    $roles = Role::objects()->filter(['code__endswith'=>"qu", "~code__startswith"=>"a"]);

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

    // fetch select * from role where id in (1,2,3)
    $roles = Role::objects()->filter(['id__in'=>[1,2,3]]);

    foreach ($roles as $role) :
        echo $role->name."===>".$role->code."<br>";
    endforeach;

.. note::

   The filter method can take one/multiple arrays that contain the conditions to
   use when filtering.

   Since its not possible to have the same array key repeated on the same array,
   use a second array to add more conditions for the same key.

   For example to query roles based on the value of the **code** field.

   This will work fine since the keys are different

    .. code-block:: php

        Role::objects()->filter(["code"=>"admin_role", "~code"=>"user_role"])

   This wont work as expected since the keys are the same

    .. code-block:: php

        Role::objects()->filter(["code"=>"admin_role", "code"=>"user_role"])

   Solve this by making another array.

    .. code-block:: php

        Role::objects()->filter(["code"=>"admin_role"], ["code"=>"user_role"])

.. toctree::
   :titlesonly:

   self
   install
   configuration
   components
   dependencies
   features
   credits