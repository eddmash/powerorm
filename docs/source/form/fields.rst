Form fields
###########

.. _built_in_form_fields:

Built-in Field classes
----------------------

Naturally, the forms library comes with a set of Field classes that represent common validation needs. This section 
documents each built-in field.

For each field, we describe:
- The default widget used if you don't specify widget.
- The value returned when you provide an empty value (see the section on required above to understand what that means).

.. _form_datefield:

DateField
---------

- Default widget: **DateInput**
- Empty value: **null**
- Normalizes to: A PHP **DateTime** object.
- Validates that the given value is either a **DateTime** or string formatted in a particular date format.
- Error message keys: **required**, **invalid**.

Takes one optional argument:

**input_formats**

    A list of formats used to attempt to convert a string to a valid datetime.date object.

If no input_formats argument is provided, the default input formats are:

.. code-block:: php

    [
       'Y-m-d',      // '2006-10-25'
       'm/d/Y',      // '10/25/2006'
       'm/d/y'     // '10/25/06'
    ]
