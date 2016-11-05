#############################
Model Fields
#############################

.. contents::
   :local:
   :depth: 2

This document contains all the API references of Field including the field options and field types PowerOrm offers.

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

choices
-----------

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

    $gender =  PModel::CharField(['maxLength'=>2, 'choices'=>$gender_choices])

dbColumn
-----------
The name of the database column to use for this field. If this isn't given, PowerOrm will use the field's name.


dbIndex
---------
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
---------------
A human-readable name for the field. If the verbose name isn't given, PowerOrm will
automatically create it using the field's attribute name, converting underscores to spaces. See
:ref:`Verbose field names <verbose-field-names>`

helpText
---------
Extra "help" text to be displayed with the form widget. It's useful for documentation even if your field isn't used on
a form.

Field types
================

AutoField
------------
An IntegerField that automatically increments according to available IDs. You usually won't need to use this directly;
a primary key field will automatically be added to your model if you don't specify otherwise.
See
:ref:`Automatic primary key fields <automatic-primary-key-fields>`

CharField
-----------------
A string field, for small- to large-sized strings.

For large amounts of text, use TextField.

The default form widget for this field is a TextInput.

CharField has one extra required argument:

- **maxLength :**
  The maximum length (in characters) of the field. The maxLength is enforced at the database level and in PowerOrm's
  validation.

EmailField
------------

**maxLength** default is 254.

A CharField that checks that the value is a valid email address. It uses EmailValidator to validate the input.

IntegerField
----------------
An integer.

The default form widget for this field is a TextInput.

TextField
-------------------
A large text field.

The default form widget for this field is a Textarea.

If you specify a **maxLength** attribute, it will be reflected in the Textarea widget of the auto-generated form field.
However it is not enforced at the model or database level. Use a CharField for that.

URLField
-----------
A CharField for a URL.

**maxLength** default is 200.

The default form widget for this field is a TextInput.

Like all CharField subclasses, URLField takes the optional maxLength argument.

If you don't specify maxLength, a default of 200 is used.

Relationship fields
======================

PowerOrm also defines a set of fields that represent relations.

ForeignKey
-------------

A many-to-one relationship. Requires a 'to' argument: the class to which the model is related.

.. _recursive_relation:

To create a recursive relationship – an object that has a many-to-one relationship with itself –
use

``PModel::ForeignKey(['to'=>'this'])``.

.. code-block:: php

    class Car extends PModel{
        public function unboundFields()
        {
            return [
                'manufacturer' => PModel::ForeignKey(['to' => 'Manufacturer'])
            ];
        }
    }

    class Manufacturer extends PModel
    {

        public function unboundFields(){
            return [];
        }
    }

ManyToManyField
------------------
A many-to-many relationship. Requires a 'to' argument: the class to which the model is related, which works exactly
the same as it does for ForeignKey.

OneToOneField
-----------------
A one-to-one relationship. Conceptually, this is similar to a ForeignKey with unique=True, but the "reverse" side of the
relation will directly return a single object.
