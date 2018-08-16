#############################
Model Fields
#############################

This document contains all the API references of Field including the field options and field types PowerOrm offers.

.. contents::
   :local:
   :depth: 2


Field options
=================

The following arguments are available to all field types. All are optional.

null
-------

If True, PowerOrm will store empty values as NULL in the database. Default is False.

blank
----------
If True, the field is allowed to be blank. Default is False.

Note that this is different than null. null is purely database-related, whereas blank is validation-related.
If a field has blank=True, form validation will allow entry of an empty value. If a field has blank=False,
the field will be required.

.. _field_choices:

choices
-------

An array consisting itself of associative arrays (e.g. ``[['f'=>'female'], ['m'=>'male', ...]]``) to use as choices
for this field.

If this is given, the default form widget will be a select box with these choices instead of the standard text field.

The key element in each associative array is the actual value to be set on the model, and the second element is the
human-readable name. For example:

.. code-block:: php

    $gender_choices = [
       'm'=>'Male',
       'f'=>'Female',
    ];

    $gender = Eddmash\PowerOrm\Model\Model::CharField(['maxLength'=>2, 'choices'=>$gender_choices])

dbColumn
-----------
The name of the database column to use for this field. If this isn't given, PowerOrm will use the field's name.


.. _model_field_db_index:

dbIndex
-------
If True, this field will be indexed.

default
---------------
The default value for the field. This can be a value or a callable object. If callable it will be called every time a
new object is created.

primaryKey
---------------
If True, this field is the primary key for the model.

unique
-------------
If True, this field must be unique throughout the table.

verboseName
-----------
A human-readable name for the field. If the verbose name isn't given, PowerOrm will
automatically create it using the field's attribute name, converting underscores to spaces. See
:ref:`Verbose field names <verbose-field-names>`

helpText
--------
Extra "help" text to be displayed with the form widget. It's useful for documentation even if your field isn't used on
a form.

validators
----------

A list of validators to run for this field. See the :doc:`validators documentation</form/validators>` for more information.

Field types
===========

.. _model_autofield:

AutoField
---------
An IntegerField that automatically increments according to available IDs. You usually won't need to use this directly;
a primary key field will automatically be added to your model if you don't specify otherwise.
See
:ref:`Automatic primary key fields <automatic-primary-key-fields>`

.. _model_charfield:

CharField
---------
A string field, for small- to large-sized strings.

For large amounts of text, use TextField.

The default form widget for this field is a TextInput.

CharField has one extra required argument:

- **maxLength :**
  The maximum length (in characters) of the field. The maxLength is enforced at the database level and in PowerOrm's
  validation.

.. _model_emailfield:

EmailField
----------

**maxLength** default is 254.

A CharField that checks that the value is a valid email address. It uses EmailValidator to validate the input.

.. _model_booleanfield:

BooleanField
------------

A true/false field.

The default form widget for this field is a CheckboxInput.

.. _model_integerfield:

IntegerField
------------
An integer.

The default form widget for this field is a TextInput.

.. _model_textfield:

TextField
---------
A large text field.

The default form widget for this field is a Textarea.

If you specify a **maxLength** attribute, it will be reflected in the Textarea widget of the auto-generated form field.
However it is not enforced at the model or database level. Use a CharField for that.

.. _model_urlfield:

URLField
--------
A CharField for a URL.

**maxLength** default is 200.

The default form widget for this field is a TextInput.

Like all CharField subclasses, URLField takes the optional maxLength argument.

If you don't specify maxLength, a default of 200 is used.

.. _model_slugfield:

SlugField
---------

Slug is a newspaper term. A slug is a short label for something, containing only letters, numbers, underscores or
hyphens. They're generally used in URLs.

Like a :ref:`CharField<model_charfield>`, you can specify **maxLength**. If **maxLength** is not specified, Powerorm
will use a default length of 50.

Implies setting Field.dbIndex to **true**.

Relationship fields
===================

PowerOrm also defines a set of fields that represent relations.

.. _model_foreignkey:

ForeignKey
----------

A many-to-one relationship. Requires a ``to`` argument: the class to which the model is related.


.. code-block:: php

    // model/Car.php
    use Eddmash\PowerOrm\Model\Model;

    class Car extends Model{
        public function unboundFields()
        {
            return [
                'manufacturer' => Model::ForeignKey(['to' => Manufacturer::class])
            ];
        }
    }

    // model/Manufacturer.php
    use Eddmash\PowerOrm\Model\Model;

    class Manufacturer extends Model
    {

        public function unboundFields(){
            return [];
        }
    }

A database index is automatically created on the ``ForeignKey``. You can disable this by
setting :ref:`dbIndex<model_field_db_index>` to ``false``.
You may want to avoid the overhead of an index if you are creating a foreign key for consistency rather than joins,
or if you will be creating an alternative index like a partial or multiple column index.

.. _related_name:

relatedName
***********
The name to use for the relation from the related object back to this one. It's also the default value for
:ref:`<_related_query_name>relatedQueryName` (the name to use for the reverse filter name from the target model).
See the :ref:`<backwards_related_objects>related objects documentation` for a full explanation and example. Note that
you must set this value when defining relations on :doc:`abstract models</orm/model/abstract>` and when you do so some
:ref:`<abstract_related_name>special syntax` is available.

If you'd prefer powerorm not to create a backwards relation, set related_name to '+' or end it with '+'. For example, 
this will ensure that the User model won't have a backwards relation to this model:

.. _related_query_name:

relatedQueryName
****************
The name to use for the reverse filter name from the target model. It defaults to the value of
:ref:`<_related_name>relatedName` or :ref:`<default_related_name>defaultRelatedName` if set, otherwise it defaults to
the name of the model.

Like :ref:`<_related_name>relatedName`, :ref:`<default_related_name>defaultRelatedName` supports app label and class
interpolation via some :ref:`<abstract_related_name>special syntax`.

.. _recursive_relation:

Recursive relationship
**********************

Recursive relationship is when an object that has a many-to-one relationship with itself.

To create a recursive relationship set the ``to`` argument to the constant ``Model::SELF`` or the name of the model
like we have done in for foreign keys.

.. code-block:: php

    Eddmash\PowerOrm\Model\Model::ForeignKey(['to'=>Model::SELF])


.. _many_to_many_field:

ManyToManyField
---------------

A many-to-many relationship. Requires a 'to' argument: the class to which the model is related, which works exactly
the same as it does for ForeignKey.

.. _through_model:

Through Model
*************

.. _model_onetoonefield:

OneToOneField
-------------
A one-to-one relationship. Conceptually, this is similar to a ForeignKey with unique=True, but the "reverse" side of the
relation will directly return a single object.
