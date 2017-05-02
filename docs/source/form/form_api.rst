The Forms API
#############

.. contents::
    :local:
    :depth: 2

..  _form_bound_and_unbound:

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

The form constructor accepts an associative as argument.

To bind data to a form, pass the data as a associative array with the key data to your Form class constructor:

.. code-block:: php

    $data =[
        "name" => "rrrr"
        "email" => "edd.cowan@gmail.com"
    ];
    $form = new AuthorForm(['data'=>$data]);

In this associative array, the keys are the field names, which correspond to the attributes in your Form class. 
The values are the data you're trying to validate. These will usually be strings, but there's no requirement that they
be strings; the type of data you pass depends on the Field, as we'll see in a moment.

.. _form_is_bound:

Form.isBound
------------

If you need to distinguish between bound and unbound form instances at runtime, check the value of the form's isBound
attribute:

.. code-block:: php

    $form = new AuthorForm();
    var_dump($form->isBound); // false

    $form = new AuthorForm(['data'=>$data]);
    var_dump($form->isBound); // true

Note that passing an empty associative array creates a bound form with empty data:

.. code-block:: php

    $form = new AuthorForm([]);
    var_dump($form->isBound); // true

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

.. _form_is_valid:

Form.isValid()
..............

The primary task of a Form object is to validate data. With a bound Form instance, call the isValid() method to run
validation and return a boolean designating whether the data was valid:


.. code-block:: php

    $data =[
        "name" => "rrrr"
        "email" => "edd.cowan@gmail.com"
    ];
    $form = new AuthorForm(['data'=>$data]);
    var_dump($form->isValid()); // true

Let's try with some invalid data. In this case, subject is blank (an error, because all fields are required by default)
and email is not a valid email address:

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

In this associative array, the keys are the field names, and the values are an array of strings representing the error
messages. The error messages are stored in an array because a field can have multiple error messages.

You can access errors without having to call :ref:`isValid()<form_is_valid>` first. The form's data will be validated
the first time either you call :ref:`isValid()<form_is_valid>` or access errors.

The validation routines will only get called once, regardless of how many times you access errors or call
:ref:`isValid()<form_is_valid>`. This means that if validation has side effects, those side effects will only be
triggered once.

Form.errors()->asData()
.......................

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
altogether; for instance from a controller.

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

.. _form_cleaned_data:

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

.. _output_form_as_html:

Outputting forms as HTML
------------------------

Form.asParagraph()
..................

**asParagraph()** renders the form as a series of <p> tags, with each <p> containing one field:


.. code-block:: php

    echo $form->asParagraph();

.. code-block:: html

    <p>
        <label for="id_subject">Subject</label>
        <input maxlength="100" type="text" name="subject" id="id_subject"> <br>

    </p>
    <p>
        <label for="id_message">Message</label> <br>
        <textarea name="message"id="id_message"></textarea>
        <br>
    </p>
    <p>
        <label for="id_email">Email</label> <br>
        <input type="email" name="email" id="id_email"> <br>
    </p>
    <p>
        <label for="id_cc_myself">Cc myself</label> <br>
        <input type="checkbox" name="cc_myself" id="id_cc_myself">
    </p>


Form.asUl()
...........

**asUl()** renders the form as a series of <li> tags, with each <li> containing one field. It does not include the <ul>
or </ul>, so that you can specify any HTML attributes on the <ul> for flexibility:

.. code-block:: php

    echo $form->asUl();

.. code-block:: html

    <li>
        <label for="id_mo-subject"> Subject</label>
        <input maxlength="100" type="text" name="mo-subject" id="id_mo-subject">
    </li>
    <li>
        <label for="id_mo-message"> Message</label>
        <textarea name="mo-message" id="id_mo-message"></textarea>
    </li>
    <li>
        <label for="id_mo-cc_myself"> Cc myself</label>
        <input type="checkbox" name="mo-cc_myself" id="id_mo-cc_myself">
    </li>
.. _form_configure_id_label:

Form.asTable()
..............

Finally, **asTable()** outputs the form as an HTML **<table>**. :

.. code-block:: html

    <tr>
        <th><label for="id_mo-subject"> Subject</label></th>
        <td><input maxlength="100" type="text" name="mo-subject" id="id_mo-subject"></td>
    </tr>
    <tr>
        <th><label for="id_mo-message"> Message</label></th>
        <td><textarea name="mo-message" id="id_mo-message"></textarea><br><span class="helptext">messages</span></td>
    </tr>
    <tr>
        <th><label for="id_mo-cc_myself"> Cc myself</label></th>
        <td><input type="checkbox" name="mo-cc_myself" id="id_mo-cc_myself"></td>
    </tr>

Configuring form elements' HTML id attributes and <label> tags
--------------------------------------------------------------

autoId
......

By default, the form rendering methods include:

- HTML id attributes on the form elements.
- The corresponding **<label>** tags around the labels. An HTML **<label>** tag designates which label text is
  associated with which form element. This small enhancement makes forms more usable and more accessible to assistive
  devices. It's always a good idea to use **<label>** tags.

The **id** attribute values are generated by prepending **id_** to the form field names. This behavior is configurable,
though, if you want to change the id convention or remove HTML **id** attributes and **<label>** tags entirely.

Use the **autoId** argument to the Form constructor to control the **id** and label behavior.
This argument must be **true**, **false** or a **string**.

- If **autoId** is **false**, then the form output will not include **<label>** tags nor **id** attributes.

  .. code-block:: php

    $f = new ContactForm(['autoId'=>false]);

    echo $f->asTable ();

    <tr><th>Subject:</th><td><input type="text" name="subject" maxlength="100" required /></td></tr>
    <tr><th>Message:</th><td><input type="text" name="message" required /></td></tr>
    <tr><th>Sender:</th><td><input type="email" name="sender" required /></td></tr>
    <tr><th>Cc myself:</th><td><input type="checkbox" name="cc_myself" /></td></tr>

    echo $f->asUl ();

    <li>Subject: <input type="text" name="subject" maxlength="100" required /></li>
    <li>Message: <input type="text" name="message" required /></li>
    <li>Sender: <input type="email" name="sender" required /></li>
    <li>Cc myself: <input type="checkbox" name="cc_myself" /></li>

    echo $f->asParagraph();

    <p>Subject: <input type="text" name="subject" maxlength="100" required /></p>
    <p>Message: <input type="text" name="message" required /></p>
    <p>Sender: <input type="email" name="sender" required /></p>
    <p>Cc myself: <input type="checkbox" name="cc_myself" /></p>

- If **autoId** is set to **true**, then the form output will include **<label>** tags and will simply use the field
  name as its id for each form field:

 .. code-block:: php

    $f = new ContactForm(['autoId'=>true]);

    echo $f->asTable ();

    <tr><th><label for="subject">Subject:</label></th><td><input id="subject" type="text" name="subject" maxlength="100" required /></td></tr>
    <tr><th><label for="message">Message:</label></th><td><input type="text" name="message" id="message" required /></td></tr>
    <tr><th><label for="sender">Sender:</label></th><td><input type="email" name="sender" id="sender" required /></td></tr>
    <tr><th><label for="cc_myself">Cc myself:</label></th><td><input type="checkbox" name="cc_myself" id="cc_myself" /></td></tr>

    echo $f->asUl ();

    <li><label for="subject">Subject:</label> <input id="subject" type="text" name="subject" maxlength="100" required /></li>
    <li><label for="message">Message:</label> <input type="text" name="message" id="message" required /></li>
    <li><label for="sender">Sender:</label> <input type="email" name="sender" id="sender" required /></li>
    <li><label for="cc_myself">Cc myself:</label> <input type="checkbox" name="cc_myself" id="cc_myself" /></li>

    echo $f->asParagraph();

    <p><label for="subject">Subject:</label> <input id="subject" type="text" name="subject" maxlength="100" required /></p>
    <p><label for="message">Message:</label> <input type="text" name="message" id="message" required /></p>
    <p><label for="sender">Sender:</label> <input type="email" name="sender" id="sender" required /></p>
    <p><label for="cc_myself">Cc myself:</label> <input type="checkbox" name="cc_myself" id="cc_myself" /></p>

- If **autoId** is set to a string containing the format character **'%s'**, then the form output will
  include **<label>** tags, and will generate **id** attributes based on the format string.
  For example, for a format string **'field_%s'**, a field named subject will get the id value **'field_subject'**.

  .. code-block:: php

    $f = new ContactForm(autoId=['autoId'=>'id_for_%s']);

    echo $f->asTable ();

    <tr><th><label for="id_for_subject">Subject:</label></th><td><input id="id_for_subject" type="text" name="subject" maxlength="100" required /></td></tr>
    <tr><th><label for="id_for_message">Message:</label></th><td><input type="text" name="message" id="id_for_message" required /></td></tr>
    <tr><th><label for="id_for_sender">Sender:</label></th><td><input type="email" name="sender" id="id_for_sender" required /></td></tr>
    <tr><th><label for="id_for_cc_myself">Cc myself:</label></th><td><input type="checkbox" name="cc_myself" id="id_for_cc_myself" /></td></tr>

    echo $f->asUl ();

    <li><label for="id_for_subject">Subject:</label> <input id="id_for_subject" type="text" name="subject" maxlength="100" required /></li>
    <li><label for="id_for_message">Message:</label> <input type="text" name="message" id="id_for_message" required /></li>
    <li><label for="id_for_sender">Sender:</label> <input type="email" name="sender" id="id_for_sender" required /></li>
    <li><label for="id_for_cc_myself">Cc myself:</label> <input type="checkbox" name="cc_myself" id="id_for_cc_myself" /></li>

    echo $f->asParagraph();

    <p><label for="id_for_subject">Subject:</label> <input id="id_for_subject" type="text" name="subject" maxlength="100" required /></p>
    <p><label for="id_for_message">Message:</label> <input type="text" name="message" id="id_for_message" required /></p>
    <p><label for="id_for_sender">Sender:</label> <input type="email" name="sender" id="id_for_sender" required /></p>
    <p><label for="id_for_cc_myself">Cc myself:</label> <input type="checkbox" name="cc_myself" id="id_for_cc_myself" /></p>

If **autoId** is set to any other true value – such as a string that doesn't include **%s** – then the library will act
as if **autoId** is **true**.

By default, **autoId** is set to the string **'id_%s'**.

Form.labelSuffix
................

A translatable string (defaults to a colon (:) in English) that will be appended after any label name when a form is 
rendered.

It's possible to customize that character, or omit it entirely, using the **labelSuffix** parameter:

.. code-block:: html

    $f = new ContactForm([autoId=>'id_for_%s', labelSuffix=>'']);

    echo $f->asUl();

    <li><label for="id_for_subject">Subject</label> <input id="id_for_subject" type="text" name="subject" maxlength="100" required /></li>
    <li><label for="id_for_message">Message</label> <input type="text" name="message" id="id_for_message" required /></li>
    <li><label for="id_for_sender">Sender</label> <input type="email" name="sender" id="id_for_sender" required /></li>
    <li><label for="id_for_cc_myself">Cc myself</label> <input type="checkbox" name="cc_myself" id="id_for_cc_myself" /></li>
    $f = new ContactForm(auto_id='id_for_%s', label_suffix=' ->')

    echo $f->asUl();

    <li><label for="id_for_subject">Subject -></label> <input id="id_for_subject" type="text" name="subject" maxlength="100" required /></li>
    <li><label for="id_for_message">Message -></label> <input type="text" name="message" id="id_for_message" required /></li>
    <li><label for="id_for_sender">Sender -></label> <input type="email" name="sender" id="id_for_sender" required /></li>
    <li><label for="id_for_cc_myself">Cc myself -></label> <input type="checkbox" name="cc_myself" id="id_for_cc_myself" /></li>

Note that the label suffix is added only if the last character of the label isn't a punctuation character
(in English, those are ., !, ? or :).

Fields can also define their own **labelSuffix**. This will take precedence over **Form.labelSuffix**. 

.. _form_binding_uploaded_field:

Binding uploaded files to a form
--------------------------------

Dealing with forms that have :ref:`FileField<form_filefield>` and :ref:`ImageField<form_imagefield>` fields is a little
more complicated than a normal form.

Firstly, in order to upload files, you'll need to make sure that your <form> element correctly defines the enctype as 
"multipart/form-data":

.. code-block:: html

    <form enctype="multipart/form-data" method="post" action="/foo/">

Secondly, when you use the form, you need to bind the file data. File data is handled separately to normal form data,
so when your form contains a :ref:`FileField<form_filefield>` and :ref:`ImageField<form_imagefield>` , you will need to
specify a second argument when you bind your form. So if we extend our ContactForm to include an
:ref:`ImageField<form_imagefield>` called mugshot,we need to bind the file data containing the mugshot image:

.. note::

    more to come soon

Testing for multipart forms
---------------------------

.. _form_is_multipart:

isMultipart()
.............

If you're writing reusable views or templates, you may not know ahead of time whether your form is a multipart form or
not. The **isMultipart()** method tells you whether the form requires multipart encoding for submission:

Prefixes for forms
------------------

prefix
...........

You can put several Powerform forms inside one **<form>** tag. To give each Form its own namespace,
use the prefix keyword argument:

