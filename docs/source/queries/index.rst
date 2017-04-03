Making queries
============================

Once you've created your data models, Powerorm automatically gives you a database-abstraction API that lets you
create, retrieve, update and delete objects.

This document explains how to use this API.

Throughout this guide (and in the reference), we'll refer to the following models, which comprise a Weblog application:

.. code-block:: php

    // models/Blog

    namespace App\Models;

    use Eddmash\PowerOrm\Model\Model;

    class Blog extends Model
    {

        private function unboundFields()
        {
            return [
                'name'=>Model::CharField(['maxLength'=>100]),
                'tagline'=>Model::TextField()
            ];
        }
    }

    // models/Author

    namespace App\Models;

    use Eddmash\PowerOrm\Model\Model;

    class Author extends Model
    {


        private function unboundFields()
        {
            return [
                'name'=>Model::CharField(['maxLength'=>200]),
                'email'=>Model::EmailField()
            ];
        }
    }


    // models/Entry
    namespace App\Models;

    use Eddmash\PowerOrm\Model\Model;

    /**
    * Class Entry
    */
    class Entry extends Model
    {
        private function unboundFields()
        {
            return [
                'blog'=>Model::ForeignKey(['to'=>Blog::class]),
                'headline'=>Model::CharField(['maxLength'=>255]),
                'blog_text'=>Model::TextField(),
                'authors'=>Model::ManyToManyField(['to'=>Author::class]),
                'n_comments'=>Model::IntegerField(),
                'n_pingbacks'=>Model::IntegerField(),
                'ratings'=>Model::IntegerField(),
            ];
        }

    }

Creating objects
----------------

To represent database-table data in PHP objects, Powerorm uses an intuitive system:

    - A model class represents a database table, and
    - an instance of that class represents a particular record in the database table.

To create an object, instantiate it using an associative array(*keys* are name of a model field, *values* are the
value to assign to the fields) to the model class, then call :ref:`save() <model_save>` to save it to the database.

Using the block model we created above :

.. code-block:: php

    $blog = new \App\Models\Blog();
    $blog->name = "Beatles Blog";
    $blog->tagline='All the latest Beatles news.';
    $blog->save();

This performs an **INSERT** SQL statement behind the scenes. Powerorm doesn't hit the database until you explicitly call
:ref:`save() <model_save>`.

The :ref:`save() <model_save>`  method has no return value.

Saving changes to objects
-------------------------

To save changes to an object that's already in the database, use :ref:`save() <model_save>`.

Given a Blog instance ``$blog`` that has already been saved to the database,
this example changes its name and updates its record in the database:

.. code-block:: php

    $blog->name = 'New name';
    $blog->save();

This performs an UPDATE SQL statement behind the scenes.
Powerorm doesn't hit the database until you explicitly call :ref:`save() <model_save>`.

Saving ForeignKey and ManyToManyField fields
--------------------------------------------

Updating a ForeignKey field works exactly the same way as saving a normal field – simply assign an object of the right
type to the field in question.
This example updates the blog attribute of an Entry instance entry, assuming appropriate instances of Entry and Blog
are already saved to the database (so we can retrieve them below):

.. code-block:: php

    $en = \App\Models\Entry::objects()->get(['pk' => 1]);
    $en->blog = \App\Models\Blog::objects()->get(['pk'=>4]);
    $en->headline = "Filtered :doc:`QuerySet <queryset>`s are unique";
    $en->blog_text = "These three :doc:`QuerySet <queryset>`s are separate.";
    $en->save();

Updating a :ref:`ManyToManyField <many_to_many_field>` works a little differently – use the add() method on the field
to add a record to the relation. This example adds the Author instance joe to the entry object:

.. code-block:: php

    $en = \App\Models\Entry::objects()->get(['pk' => 1]);
    $en->authors->add([\App\Models\Author::objects()->get(['name'=>'joe'])]);

To add multiple records to a  :ref:`ManyToManyField <many_to_many_field>` in one go, include multiple arguments in
the call to add(), like this:


.. code-block:: php

    $en = \App\Models\Entry::objects()->get(['pk' => 1]);
    $authors = [];
    $authors[] = \App\Models\Author::objects()->get(['name'=>'paul']);
    $authors[] = \App\Models\Author::objects()->get(['name'=>'john']);
    $authors[] = \App\Models\Author::objects()->get(['name'=>'george']);
    $authors[] = \App\Models\Author::objects()->get(['name'=>'joe']);
    $en->authors->add($authors);

.. note::

    Powerorm will complain if you try to assign or add an object of the wrong type.

Retrieving objects
------------------

To retrieve objects from your database, construct a :doc:`QuerySet <queryset>` via a Manager on your model class.

A :doc:`QuerySet <queryset>` represents a collection of objects from your database. It can have zero, one or many filters.
Filters narrow down the query results based on the given parameters.
In SQL terms, a :doc:`QuerySet <queryset>` equates to a SELECT statement, and a filter is a limiting clause such as WHERE or LIMIT.

You get a :doc:`QuerySet <queryset>` by using your model's Manager. Each model has at least one Manager,
which is created by the static method object(). Access it directly via the model class, like so:

.. code-block:: php

    \App\Models\Blog::objects()

The Manager is the main source of a :doc:`QuerySet <queryset>` for a model.

For example, ``App\Models\Blog->objects->all()`` returns a:doc:`QuerySet <queryset>` that contains all Blog objects in
the database.

Retrieving all objects
----------------------

The simplest way to retrieve objects from a table is to get all of them. To do this, use the all() method on a Manager:

.. code-block:: php

    \App\Models\Blog::objects()->all()

The all() method returns a :doc:`QuerySet <queryset>` of all the objects in the database.


Retrieving specific objects with filters
----------------------------------------

The :doc:`QuerySet <queryset>` returned by all() describes all objects in the database table. Usually, though, you'll need to select
only a subset of the complete set of objects.

To create such a subset, you refine the initial :doc:`QuerySet <queryset>`, adding filter conditions.
The two most common ways to refine a :doc:`QuerySet <queryset>` are:

**filter()**
............

Returns a new :doc:`QuerySet <queryset>` containing objects that match the given lookup parameters.

**exclude()**
.............

Returns a new :doc:`QuerySet <queryset>` containing objects that do not match the given lookup parameters.

This method take an associative array of parameters.

For example, to get a :doc:`QuerySet <queryset>` of Authors who have the letter 'joe' in there name, use filter() like so:


.. code-block:: php

    \App\Models\Author::objects()->filter(['name__contains'=>'joe'])

Chaining filters
----------------

The result of refining a :doc:`QuerySet <queryset>` is itself a :doc:`QuerySet <queryset>`, so it's possible to chain refinements together. For example:

.. code-block:: php
        
    \App\Models\Entry::objects()
        ->filter(['headline__startswith'=>'what'])
        ->exclude(['rating__lte'=>3])
        ->filter(['blog_text__contains'=>'kenya']);

This takes the initial :doc:`QuerySet <queryset>` of all entries in the database, adds a filter, then an exclusion,
then another filter.
The final result is a :doc:`QuerySet <queryset>` containing all entries with a headline that starts with "What", 
excluding any entries with a rating of 3 or less and the blog_text contains the word 'kenya'.

Filtered QuerySets are unique
-----------------------------

Each time you refine a QuerySet, you get a brand-new QuerySet that is in no way bound to the previous QuerySet.
Each refinement creates a separate and distinct QuerySet that can be stored, used and reused.

Example:

.. code-block:: php

     $qs1 = \App\Models\Entry::objects()->filter(['headline__startswith' => 'what']);
     $qs2 = $qs1->exclude(['rating__gte' => 3]);
     $qs3 = $qs1->filter(['blog_text__contains' => 'kenya']);

These three :doc:`QuerySet <queryset>` are separate. The first is a base :doc:`QuerySet <queryset>` containing all
entries that contain a headline starting with “What”.
The second is a subset of the first, with an additional criteria that excludes any entries with a rating of 3 or less.
The third is a subset of the first, with an additional criteria that selects only the records
whose the blog_text contains the word 'kenya'.

The initial :doc:`QuerySet <queryset>` ($q1) is unaffected by the refinement process.

QuerySets are lazy
------------------

:doc:`QuerySet <queryset>` are lazy – the act of creating a :doc:`QuerySet <queryset>` doesn't involve any database
activity. You can stack filters together all day long, and Powerorm won't actually run the query until
the :doc:`QuerySet <queryset>` is evaluated.

Take a look at this example:

.. code-block:: php

    $qs = \App\Models\Entry::objects()->filter(['headline__startswith' => 'what']);
    $qs = $qs->exclude(['ratings' => 3]);
    $qs = $qs->filter(['blog_text__contains' => 'kenya']);
    var_dump($qs);

Though this looks like three database hits, in fact it hits the database only once, at the last line (var_dump($qs)).
In general, the results of a :doc:`QuerySet <queryset>` aren't fetched from the database until you "ask" for them.
When you do, the :doc:`QuerySet <queryset>` is evaluated by accessing the database.

For more details on exactly when evaluation takes place, see :ref:`When QuerySets are evaluated <querset_evaluation>`.

.. toctree::
    :caption: More Queries Information
    :titlesonly:

    self
    queryset