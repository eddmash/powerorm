Form fields
###########

.. contents::
    :local:
    :depth: 2

Core field arguments
--------------------

Each Field class constructor takes at least these arguments. Some Field classes take additional, field-specific
arguments, but the following should always be accepted:

required
........

By default, each Field class assumes the value is required, so if you pass an empty value – either **null** or
the **empty** string ("") – then **clean()** will raise a **ValidationError** exception.

label
.....

The label argument lets you specify the “human-friendly” label for this field. This is used when the Field is displayed
in a Form.

widget
......

The widget argument lets you specify a Widget class to use when rendering this Field. See :doc:`Widgets<widgets>` for more
information.

helpText
........

The helpText argument lets you specify descriptive text for this Field. If you provide helpText, it will be displayed 
next to the Field when the Field is rendered by one of the convenience Form methods (e.g., asUl()).

Like the model field's help_text, this value isn't HTML-escaped in automatically-generated forms.

validators
..........

The validators argument lets you provide a list of validation functions for this field.

See the :doc:`validators<validators>` documentation for more information.

disabled
........

The disabled boolean argument, when set to **true**, disables a form field using the **disabled** HTML attribute so
that it won't be editable by users. Even if a user tampers with the field's value submitted to the server, it will be
ignored in favor of the value from the form's initial data.

.. _built_in_form_fields:

Built-in Field classes
----------------------

Naturally, the forms library comes with a set of Field classes that represent common validation needs. This section 
documents each built-in field.

For each field, we describe:
- The default widget used if you don't specify widget.
- The value returned when you provide an empty value (see the section on required above to understand what that means).

.. _form_integerfield:

IntegerField
............

- Default widget: :ref:`NumberInput<numberinput_widget>`.
- Empty value: **null**
- Normalizes to: A php **integer**.
- Validates that the given value is an integer. Leading and trailing whitespace is allowed, as in php's **intval()**
  function.
- Error message keys: **required**, **invalid**, **maxValue**, **minValue**.

The **maxValue** and **minValue** error messages may contain **%(limit_value)s**, which will be substituted by the
appropriate limit.

Takes two optional arguments for validation:

.. _form_integer_max_value:

**maxValue**

.. _form_integer_min_value:

**min_value**

    These control the range of values permitted in the field.

.. _form_booleanfield:

BooleanField
............

- Default widget: :ref:`CheckboxInput<widget_checkboxinput>`
- Empty value: **false**
- Normalizes to: A php boolean **true** or **false** value.
- Validates that the value is **true** (e.g. the check box is checked) if the field has **required=true**.
- Error message keys: **required**

.. note::

    Since all Field subclasses have **required=true** by default, the validation condition here is important. If you
    want to include a boolean in your form that can be either True or False (e.g. a checked or unchecked checkbox),
    you must remember to pass in **required=false** when creating the **BooleanField**.

.. _form_charfield:

CharField
.........

- Default widget: :ref:`TextInput<textinput_widget>`
- Empty value: **''** (an empty string)
- Normalizes to: A string.
- Validates **maxLength** or **minLength**, if they are provided. Otherwise, all inputs are valid.
- Error message keys: **required**, **maxLength**, **minLength**

Has three optional arguments for validation:

.. _form_charfield_maxlength:

**maxLength**

.. _form_charfield_minlength:

**minLength**

    If provided, these arguments ensure that the string is at most or at least the given length.

.. _form_charfield_strip:

**strip**

    If True (default), the value will be stripped of leading and trailing whitespace.

.. _form_urlfield:

URLField
........

- Default widget: :ref:`URLInput<urlinput_widget>`
- Empty value: **''** (an empty string)
- Normalizes to: A string.
- Validates that the given value is a valid URL.
- Error message keys: **required**, **invalid**

Takes the following optional arguments:

**maxLength**

**minLength**
    These are the same as :ref:`CharField.maxLength<form_charfield_maxlength>` and
    :ref:`CharField.minLength<form_charfield_minlength>`.

.. _form_emailfield:

EmailField
..........

- Default widget: :ref:`EmailInput<emailinput_widget>`
- Empty value: **''** (an empty string)
- Normalizes to: A string.
- Validates that the given value is a valid email address, using a moderately complex regular expression.
- Error message keys: **required**, **invalid**

Has two optional arguments for validation, **minLength** and **minLength**. If provided, these arguments ensure that
the string is at most or at least the given length.


.. _form_slugfield:

SlugField
.........

- Default widget: :ref:`TextInput<textinput_widget>`
- Empty value: **''** (an empty string)
- Normalizes to: A string.
- Validates that the given value contains only letters, numbers, underscores, and hyphens.
- Error message keys: **required**, **invalid**

.. _form_decimalfield:

DecimalField
............

- Default widget: :ref:`NumberInput<numberinput_widget>`
- Empty value: **null** (an empty string)
- Normalizes to: A float.
- Validates that the given value is a decimal. Leading and trailing whitespace is ignored..
- Error message keys: **required**, **invalid**, **maxValue**, **minValue**, **maxDigits**, **maxDecimalPlaces**,
  **maxWholeDigits**

The **maxValue** and **minValue** error messages may contain **%(limit_value)s**, which will be substituted by the
appropriate limit.

Similarly, the **maxDigits**, **maxDecimalPlaces**, and **maxWholeDigits** error messages may contain **%(max)s**.

Takes four optional arguments:

**maxValue**

**minValue**
    These control the range of values permitted in the field, and should be given as float values.

**maxDigits**

    The maximum number of digits (those before the decimal point plus those after the decimal point, with leading zeros
    stripped) permitted in the value.

**maxDecimalPlaces**

    The maximum number of decimal places permitted.

.. _form_datefield:

DateField
.........

- Default widget: :ref:`DateInput<dateinput_widget>`
- Empty value: **null**
- Normalizes to: A PHP **DateTime** object.
- Validates that the given value is either a **DateTime** or string formatted in a particular date format.
- Error message keys: **required**, **invalid**.

Takes one optional argument:

.. _form_datefield_input_format:

**input_formats**

    A list of formats used to attempt to convert a string to a valid datetime.date object.

If no input_formats argument is provided, the default input formats are:

.. code-block:: php

    [
       'Y-m-d',      // '2006-10-25'
       'm/d/Y',      // '10/25/2006'
       'm/d/y'     // '10/25/06'
    ]

.. _form_choicefield:

ChoiceField
...........

- Default widget: :ref:`Select<widget_select>`
- Empty value: **''** (an empty string)
- Normalizes to: A Unicode object.
- Validates that the given value exists in the list of choices.
- Error message keys: **required**, **invalid_choice**

The **invalid_choice** error message may contain **%(value)s**, which will be replaced with the selected choice.

Takes one extra required argument:

**choices**

    Either an associative array to use as choices for this field, or a callable that returns such an array. This
    argument accepts the same formats as the **choices** argument to a model field. See the
    :ref:`model field reference documentation on choices<field_choices>` for more details. If the argument is a callable,
    it is evaluated each time the field's form is initialized.

.. _form_multiplechoicefield:

MultipleChoiceField
...................

- Default widget: :ref:`SelectMultiple<widget_selectmultiple>`
- Empty value: **[]** (an empty list)
- Normalizes to: A list of php objects.
- Validates that every value in the given list of values exists in the list of choices.
- Error message keys: **required**, **invalid_choice**, **invalid_list**

The **invalid_choice** error message may contain **%(value)s**, which will be replaced with the selected choice.

Takes one extra required argument, **choices**, as for :ref:`ChoiceField<form_choicefield>`.

.. _form_filefield:

FileField
.........

to be added

.. _form_imagefield:

ImageField
..........

to be added