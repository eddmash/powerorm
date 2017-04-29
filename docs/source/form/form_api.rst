The Forms API
#############

Bound and unbound forms
-----------------------

A Form instance is either bound to a set of data, or unbound.

If it's bound to a set of data, it's capable of validating that data and rendering the form as HTML with the data
displayed in the HTML.

If it's unbound, it cannot do validation (because there's no data to validate!), but it can still render the blank form
as HTML.

.. _form_class:

class Form
----------

To create an unbound Form instance, simply instantiate the class:

.. code-block:: php

    $form = new AuthorForm();

To bind data to a form, pass the data as a associative array as the first parameter to your Form class constructor:

.. code-block:: php

    $data =[
        "name" => "rrrr"
        "email" => "edd.cowan@gmail.com"
    ];
    $form = new AuthorForm(['data'=>$data]);

In this associative array, the keys are the field names, which correspond to the attributes in your Form class. 
The values are the data you're trying to validate. These will usually be strings, but there's no requirement that they
be strings; the type of data you pass depends on the Field, as we'll see in a moment.

Form.isBound()
--------------

If you need to distinguish between bound and unbound form instances at runtime, check the value of the form's is_bound
attribute:

.. code-block:: php

    $form = new AuthorForm();
    var_dump($form->isBound()); // false

    $form = new AuthorForm(['data'=>$data]);
    var_dump($form->isBound()); // true

Note that passing an empty associative array creates a bound form with empty data:

.. code-block:: php

    $form = new AuthorForm([]);
    var_dump($form->isBound()); // true

If you have a bound Form instance and want to change the data somehow, or if you want to bind an unbound Form instance
to some data, create another Form instance. There is no way to change data in a Form instance.

Once a Form instance has been created, you should consider its data immutable, whether it has data or not.

Using forms to validate data
----------------------------

.. _form_clean:

Form.clean()
............

Implement a clean() method on your Form when you must add custom validation for fields that are interdependent.
See :ref:`Cleaning and validating fields that depend on each other <validating_fields_with_clean>` for example usage.

Form.isValid()
..............

The primary task of a Form object is to validate data. With a bound Form instance, call the is_valid() method to run
validation and return a boolean designating whether the data was valid:


.. code-block:: php

    $data =[
        "name" => "rrrr"
        "email" => "edd.cowan@gmail.com"
    ];
    $form = new AuthorForm(['data'=>$data]);
    var_dump($form->isValid()); // true

Let's try with some invalid data. In this case, subject is blank (an error, because all fields are required by default)
and sender is not a valid email address:

.. code-block:: php

    $data =[
        "name" => "rrrr"
        "email" => "edd.gmail.com"
    ];
    $form = new AuthorForm(['data'=>$data]);
    var_dump($form->isValid()); // false

.. _form_errors:

Form.errors()
.............

Access the errors method to get a associative array of error messages:

.. code-block:: php

    var_dump($form->errors());

    [
      "name" => [
        ValidationError { }
      ]
      "email" => [
        ValidationError { }
      ]
    ]

Returns an associative array of fields to their original ValidationError instances.

.. _form_add_error:

Form.addError($field, $error)
.............................

This method allows adding errors to specific fields from within the **Form.clean()** method, or from outside the form
altogether; for instance from a view.

The **field** argument is the name of the field to which the errors should be added. If its value is None the error
will be treated as a non-field error as returned by :ref:`Form.nonFieldErrors() <non_field_errors>`.

The error argument can be a simple string, or preferably an instance of ValidationError. See
:ref:`Raising ValidationError<raising_validation_error>` for best practices when defining form errors.

Note that **Form.addError()** automatically removes the relevant field from ****cleanedData****.

.. _form_has_error:

Form.hasError($field, $code=null)
.................................

This method returns a boolean designating whether a field has an error with a specific error **code**.
If **code** is **null**, it will return **true** if the field contains any errors at all.

To check for non-field errors use :ref:`NON_FIELD_ERRORS<non_field_errors>` as the field parameter.

.. _non_field_errors:

Form.nonFieldErrors()
.....................

This method returns the list of errors from :ref:`Form.errors()<form_errors>` that aren't associated with a
particular field. This includes ValidationErrors that are raised in :ref:`Form.clean()<form_clean>` and errors added
using :ref:`Form.addError(null, "...")<form_add_error>`.

Dynamic initial values
----------------------

.. _form_initial:

Form.initial
............

Use **initial** to declare the initial value of form fields at runtime. For example, you might want to fill in a 
username field with the username of the current session.

To accomplish this, use the initial argument to a Form. This argument, if given, should be a associative array mapping 
field names to initial values. Only include the fields for which you're specifying an **initial** value; it's not
necessary to include every field in your form. For example:

.. code-block:: php

    $data = []; // that the form is validated against.mostly will be from post
    $initial = ['subject'=>"yello there"];
    $form = ContactForm(['data'=>$data, 'initial'=>$initial])

These values are only displayed for unbound forms, and they're not used as fallback values if a particular value isn't
provided.

If a Field defines initial and you include initial when instantiating the Form, then the latter **initial** will have
precedence. In this example, **initial** is provided both at the field level and at the form instance level, and the
latter gets precedence:

.. code-block:: php

    class ContactForm extends Form
    {
        public function fields()
        {
            return [
                'subject' => Form::CharField(['maxLength' => 100, 'initial'=>'welcome']),
                'recipients' => MultiEmailField::instance(),
                'cc_myself' => Form::BooleanField(['required' => false]),
            ];
        }
    }

.. code-block:: html

    <input maxlength="100" name="subject" id="id_subject" value="yello there" type="text">


Form.getInitialForField($field, $name)
......................................

Use **getInitialForField()** to retrieve initial data for a form field. It retrieves data from **Form.initial** and
**Field.initial**, in that order, and evaluates any callable initial values.

Accessing the fields from the form
----------------------------------

Form.getFields()
................

You can access the fields of Form instance from its getFields() method:

.. code-block:: php

    var_dump($form->getFields());

    [
      "subject" => CharField { }
      "recipients" => MultiEmailField { }
      "cc_myself" => BooleanField { }
    ]

Accessing "clean" data
----------------------

Form.cleanedData
................

Each field in a Form class is responsible not only for validating data, but also for "cleaning" it – normalizing it to
a consistent format. This is a nice feature, because it allows data for a particular field to be input in a variety
of ways, always resulting in consistent output.

For example, DateField normalizes input into a PhP DateTime object. Regardless of whether you pass it a string in 
the format '1994-07-15', a DateTime object, or a number of other formats, DateField will always normalize it to a 
DateTime object as long as it's valid.

Once you've created a Form instance with a set of data and validated it, you can access the clean data via its
cleanedData attribute:

.. code-block:: php

    $data = [
      "subject" => "help yo",
      "recipients" => "fred@example.com,edd@gmail.com",
      "cc_myself" => true
    ];

    $form = new ContactForm(['data'=>$data]);
    $form->isValid();
    var_dump($form->cleanedData);

    [
      "subject" => "help yo"
      "recipients" => [
        "fred@example.com"
        "edd@gmail.com"
      ]
      "cc_myself" => true
    ]

If your data does not validate, the **cleanedData** associative array contains only the valid fields:

.. code-block:: php

    $data = [
      "subject" => "help yo",
      "recipients" => "invalid email",
      "cc_myself" => true
    ];

    $form = new ContactForm(['data'=>$data]);
    $form->isValid();
    var_dump($form->cleanedData);

    [
      "subject" => "help yo",
      "cc_myself" => true
    ]

**cleanedData** will always only contain a key for fields defined in the Form, even if you pass extra data when you 
define the Form. In this example, we pass a bunch of extra fields to the ContactForm constructor, but **cleanedData**
contains only the form's fields:

.. code-block:: php

    $data = [
      "subject" => "help yo"
      "recipients" => "invalid email"
      "cc_myself" => "on"
      "Send" => "Send"
    ]

    $form = new ContactForm(['data'=>$data]);
    $form->isValid();
    var_dump($form->cleanedData);

    [
      "subject" => "help yo"
      "cc_myself" => true
    ]

When the Form is valid, **cleanedData** will include a key and value for all its fields, even if the data didn't 
include a value for some optional fields. In this example, the data associative array doesn't include a value for the
**box** field, but **cleanedData** includes it, with an empty value:

.. code-block:: php

    $data = [
      "subject" => "help there"
      "recipients" => "fred@example.com"
      "cc_myself" => "on"
      "Send" => "Send"
    ];

    $form = new ContactForm(['data'=>$data]);
    $form->isValid();
    var_dump($form->cleanedData);

    [
      "subject" => "help there"
      "recipients" => []
      "cc_myself" => true
      "box" => ""
    ];

In this above example, the **cleanedData** value for **box** is set to an empty string, because **box** is **CharField**,
and **CharFields** treat empty values as an empty string. Each field type knows what its "blank" value is – e.g.,
for DateField, it's null instead of the empty string. For full details on each field's behavior in this case,
see the "Empty value" note for each field in the "Built-in Field classes" section below.

You can write code to perform validation for particular form fields (based on their name) or for the form as a whole
(considering combinations of various fields). More information about this is in
:doc:`Form and field validation<validations>`.

Outputting forms as HTML
------------------------

Form.asParagraph()
..................

**asParagraph()** renders the form as a series of <p> tags, with each <p> containing one field:

orm.asUl()
..........

**asUl()** renders the form as a series of <li> tags, with each <li> containing one field. It does not include the <ul>
or </ul>, so that you can specify any HTML attributes on the <ul> for flexibility: