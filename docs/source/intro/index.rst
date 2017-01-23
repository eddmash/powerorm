#######################
Introduction
#######################

I created this project because i required a lightweight easy to use orm that i could use in my Codeigniter projects
with the least amount of configuration . sort of plug and play if you will. while at the same time reducing repetition
and providing a consistent way to deal with databases.

That is, i wanted to avoid the repetitive actions of creating migration files, creating query method to query the
database and also wanted to be able to see all my database table fields on my models without me going to the database
tables themselves and use this fields to interact with the database.

This ORM is heavily inspired by Django ORM. Because i personally love how there orm works.

Powerorm at a glance
-------------------------

This is an informal overview of how to write a database-driven Web app with Powerorm.
The goal of this document is to give you enough technical specifics to understand how Powerorm works.

Design your model
----------------------
Powerorm is an object-relational mapper in which you describe your database layout in PHP code.

The data-model syntax offers many rich ways of representing your models – so far. Here's a quick example:

.. code-block:: php

    // application/models/Role.php

    class Role extends PModel
    {
        public function unboundFields() {
            return [
                'name' => PModel::CharField(['maxLength' => 40, 'dbIndex' => true]),
                'code' => PModel::CharField(['maxLength' => 10, 'dbIndex' => true]),
            ];
        }

    }


Install it
--------------

Next, run the Powerorm command-line utility to create the database tables automatically:
::

    $ php pmanager.php migrate


The migrate command looks at all your available models and creates tables in your database for whichever
tables don't already exist, as well as optionally providing much richer schema control.

Persisting Objects to the Database
-------------------------------------

Now that you have mapped the Role model to its corresponding role table, you're ready to persist Role objects to
the database. From inside a controller, this is pretty easy.

Add the following method to the Welcome Controller:

.. code-block:: php

    // application/controllers/Welcome.php

    public function saverole()
    {
        $role = new Role();
        $role->name = "test role";
        $role->code = "test_role";
        $role->save();
        var_dump("saved ".$role->id);

        // ... other logic and response
    }


Fetching Objects from the Database
-------------------------------------

Fetching an object back out of the database is even easier. For example, suppose you've configured a route to display a
specific Product based on its id value:

When you query for a particular type of object, you always use what's known as its "manager".
You can think of a manager as a PHP class whose only job is to help you fetch models of a certain class.
You can access the manager object for an model class via the objects() method:

.. code-block:: php

    // application/controllers/Welcome.php


    public function filterExamples()
    {
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

        // ... other logic and response
    }

.. note::

   The filter method can take one/multiple arrays that contain the conditions to use when filtering.

   Since its not possible to have the same array key repeated on the same array, use a second array to add more
   conditions for the same key.

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
   dependencies
   features
   credits