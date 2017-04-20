QuerySet API reference
######################

This document describes the details of the QuerySet API.

It builds on the material presented in the model and database query guides,
so you’'ll probably want to read and understand those documents before reading this one.

Throughout this reference we'll use the example Weblog models presented in the :doc:`database query guide <index>`.

.. contents::
    :local:
    :depth: 2

When QuerySets are evaluated
----------------------------

Internally, a QuerySet can be constructed, filtered, and generally passed around without actually hitting the database.
No database activity actually occurs until you do something to evaluate the queryset.

.. _querset_evaluation:

You can evaluate a QuerySet in the following ways:

- Iteration.
    A QuerySet is iterable, and it executes its database query the first time you iterate over it.
    For example, this will print the headline of all entries in the database:

    .. code-block:: php

        $entries = \App\Models\Entry::objects()->all();
        foreach ($entries as $entry) :
            echo $entry->headline . "<br>";
        endforeach;

- dumping the queryset.
    A QuerySet is evaluated when you call var_dump(), print_r() or symfony's dump() on it.
    This is for convenience so you can immediately see your results when using the API.

    .. code-block:: php

        var_dump(\App\Models\Entry::objects()->all());

- count().
    A QuerySet is evaluated when you call count() on it. This, as you might expect, returns the length of the result list.

    .. note::

        If you only need to determine the number of records in the set (and don't need the actual objects),
        it's much more efficient to handle a count at the database level using SQL’s SELECT COUNT(*).
        Powerorm provides a :ref:`count() <queryset_count>` method for precisely this reason.

Methods that return new QuerySets
---------------------------------

Powerorm provides a range of QuerySet refinement methods that modify either the types of results returned by the
QuerySet or the way its SQL query is executed.


.. _queryset_filter:

filter()
........

Returns a new QuerySet containing objects that match the given lookup parameters.
Multiple parameters are joined via AND in the underlying SQL statement.


.. _queryset_exclude:

exclude()
.........

Returns a new QuerySet containing objects that do not match the given lookup parameters.

.. _queryset_all:

all()
.....

Returns a copy of the current QuerySet (or QuerySet subclass). This can be useful in situations where you might want
to pass in either a model manager or a QuerySet and do further filtering on the result.
After calling all() on either object, you.ll definitely have a QuerySet to work with.

When a QuerySet is :ref:`evaluated<querset_evaluation>`, it typically caches its results.
If the data in the database might have changed since a QuerySet was evaluated, you can get updated results for the same
query by calling all() on a previously evaluated QuerySet.



.. _queryset_limit:

limit()
.......


Methods that do not return QuerySets
------------------------------------

The following QuerySet methods evaluate the QuerySet and return something other than a QuerySet. 

.. _queryset_get:

get()
.....


Returns the object matching the given lookup parameters, which should be in the format described in Field lookups.

get() raises **MultipleObjectsReturned** if more than one object was found.

get() raises a **DoesNotExist exception** if an object wasn't found for the given parameters.


.. _queryset_count:

count()
.......

Returns an integer representing the number of objects in the database matching the QuerySet.
The count() method never raises exceptions.

Example:

# Returns the total number of entries in the database.

.. code-block:: php

    // Returns the total number of entries in the database.
    echo \App\Models\Entry::objects()->count();

    // Returns the number of entries whose headline starts with 'what'
    echo \App\Models\Entry::objects()->filter(['headline__startswith' => 'what'])->count();

A count() call performs a SELECT COUNT(*) behind the scenes, so you should always use count() rather than loading all
of the record into PHP objects and calling count() on the result
(unless you need to load the objects into memory anyway, in which case count() will be faster).


Note that if you want the number of items in a QuerySet and are also retrieving model instances from it
(for example, by iterating over it), it's probably more efficient to use count(queryset)
which won't cause an extra database query like Queryset::count() would.

.. _queryset_exists:

exists()
........

Returns True if the QuerySet contains any results, and False if not.

This tries to perform the query in the simplest and fastest way possible, but it does execute nearly the same query as
a normal QuerySet query.

exists() is useful for searches relating to both object membership in a QuerySet and to the existence of any objects in
a QuerySet, particularly in the context of a large QuerySet.

The most efficient method of finding whether a model with a unique field (e.g. primary_key) is a member of a QuerySet is:

.. code-block:: php

    if(Entry::objects()->filter(['pk'=>123])->exists()):
        ... code
    endif;

Field lookups
-------------

Field lookups are how you specify the meat of an SQL WHERE clause. They're specified as keyword arguments to the
QuerySet methods :ref:`filter()<queryset_filter>`, :ref:`exclude()<queryset_exclude>` and :ref:`get()<queryset_get>`.

For an introduction, see :doc:`models and database queries documentation <index>`.

Powerorms' built-in lookups are listed below. It is also possible to write :doc:`custom lookups <custom_lokup>` for
model fields.

As a convenience when no lookup type is provided (like in ``Entry::objects()->get(['id'=>14])``) the lookup type is assumed
to be :ref:`exact<lookup_exact>`.

.. _lookup_exact:

exact
.....

Exact match. If the value provided for comparison is **null**, it will be interpreted as an SQL NULL
(see :ref:`isnull <lookup_isnull>` for more details).

Examples:

.. code-block:: php

    Entry::objects()->filter(['pk__exact'=>14])
    Entry::objects()->filter(['pk__exact'=>null])

SQL equivalents:

.. code-block:: php

    SELECT ... WHERE id = 14;
    SELECT ... WHERE id IS NULL;


.. _lookup_isnull:

isnull
......

Takes either **true** or **false**, which correspond to SQL queries of **IS NULL** and **IS NOT NULL**, respectively.

Example:

.. code-block:: php

    Entry::objects()->filter(['pk__isnull'=>true])

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE id IS NULL;


.. _lookup_contains:

icontains
.........

Case-insensitive containment test.

Example:

.. code-block:: php

    Entry::objects()->get(['blog_text__icontains'=>'sequi']);

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE blog_text LIKE '%sequi%';

Note this will match the blog_text 'Sequi honored today' and 'sequi honored today'.

.. _lookup_in:

in
...

In a given list.

Example:

.. code-block:: php

    Entry::objects()->filter(['pk__in'=>[2,5,3]]);

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE id IN (2,5,3);

You can also use a queryset to dynamically evaluate the list of values instead of providing a list of literal values:

.. code-block:: php

    inner_qs = Blog::objects()->filter(['name__icontains'=>'dolor']);
    entries = Entry::objects()->filter(['blog__in'=>inner_qs)

This queryset will be evaluated as subselect statement:

.. code-block:: sql

    SELECT ... WHERE blog.id IN (SELECT id FROM ... WHERE NAME LIKE '%dolor%')

.. _lookup_gt:

gt
...

Greater than.

Example:

.. code-block:: php

    Entry::objects()->filter(['pk__gt'=>1])

SQL equivalent:


.. code-block:: sql

    SELECT ... WHERE id > 4;


.. _lookup_gte:

gte
...

Greater than or equal to.


.. _lookup_lt:

lt
..

Less than.

.. _lookup_lte:

lte
...

Less than or equal to.


.. _lookup_istartswith:

istartswith
...........

Case-insensitive starts-with.

Example:

.. code-block:: php

    Entry::objects()->filter(['headline__iendswith'=>'Will'])

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE headline ILIKE 'Will%';


.. _lookup_iendswith:

iendswith
.........

Case-insensitive ends-with.

Example:

.. code-block:: php

    Entry::objects()->filter(['headline__iendswith'=>'Will'])

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE headline ILIKE '%will'


range
.....

Range test (inclusive).

Example:

.. code-block:: php

    $date = new \DateTime('2005-01-01');
    $date2 = new \DateTime('2005-05-01');
    Entry::objects()->filter(['pub_date__range'=>[$date, $date2]]);

SQL equivalent:

.. code-block:: sql

    SELECT ... WHERE pub_date BETWEEN '2005-01-01' and '2005-05-01';

