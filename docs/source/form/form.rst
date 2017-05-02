Working with forms
##################

.. contents::
    :local:
    :depth: 2

Handling forms is a complex business. where numerous items of data of several different types may need to be:

- prepared for display in a form,
- rendered as HTML,
- edited using a convenient interface,
- returned to the server,
- validated and cleaned up, and then saved or passed on for further processing.

Powerform functionality can simplify and automate vast portions of this work, and can also do it more securely
than most programmers would be able to do in code they wrote themselves.

Powerform handles three distinct parts of the work involved in forms:

- preparing and restructuring data to make it ready for rendering.
- creating HTML forms for the data
- receiving and processing submitted forms and data from the client.

It is possible to write code that does all of this manually, but Powerform can take care of it all for you.


Building a form
---------------

The work that needs to be done

Suppose you want to create a simple form on your website, in order to obtain the user's name. You'd need something like
this in your template:

.. code-block:: html

    <form action="/your-name/" method="post">

        <label for="your_name">Your name: </label>
        <input id="your_name" type="text" name="your_name" value="">

        <input type="submit" value="OK">

    </form>

This tells the browser to return the form data to the URL **/your-name/**, using the **POST** method. It will display a
text field, labeled "Your name:", and a button marked "OK".

When the form is submitted, the POST request which is sent to the server will contain the form data.

Now you'll also need a view corresponding to that **/your-name/** URL which will find the appropriate key/value pairs
in the request, and then process them.

This is a very simple form. In practice, a form might contain dozens or hundreds of fields, many of which might need to
be pre-populated, and we might expect the user to work through the edit-submit cycle several times before concluding
the operation.

We might require some validation to occur in the browser, even before the form is submitted; we might want to use much
more complex fields, that allow the user to do things like pick dates from a calendar and so on.

At this point it's much easier to get Powerform to do most of this work for us.

Form class
----------

At the heart of this system of components is Powerforms' :ref:`Form<form_class>` class. In much the same way that a
Powerorm model describes the logical structure of an object, its behavior, and the way its parts are represented to us,
a :ref:`Form<form_class>` class describes a form and determines how it works and appears.

In a similar way that a model class's fields map to database fields, a form class's fields map to HTML form <input>
elements. (A :ref:`Model Form<model_form_class>` maps a model class's fields to HTML form <input> elements via a Form)

A form's fields are themselves classes; they manage form data and perform validation when a form is submitted.
A :ref:`DateField<form_datefield>` and a :ref:`FieldField<form_datefield>` handle very different kinds of data and have
to do different things with it.

A form field is represented to a user in the browser as an HTML "widget" - a piece of user interface machinery.
Each field type has an appropriate default Widget class, but these can be overridden as required.

Building a form in Powerform
----------------------------

**The Form class**

We already know what we want our HTML form to look like. Our starting point for it in Powerform is this:

.. code-block:: php

    namespace App\Forms;


    use Eddmash\PowerOrm\Form\Form;

    class CommentForm extends Form
    {
        /**
         * @inheritDoc
         */
        public function fields()
        {

            return [
                'your_name' => Form::CharField(['label'=>'Your name', 'maxLength'=>100]),
            ];
        }

    }

This defines a :ref:`Form<form_class>` class with a field (your_name). We've applied a human-friendly label to the
field, which will appear in the <label> when it's rendered (although in this case, the label we specified is actually
the same one that would be generated automatically if we had omitted it).

The field's maximum allowable length is defined by :ref:`maxLength<form_charfield_maxlength>`. This does two things.:

- It puts a **maxlength="100"** on the HTML **<input>** (so the browser should prevent the user from entering more than
  that number of characters in the first place).
- It also means that when Powerform receives the form back from the browser,
  it will validate the length of the data.

A :ref:`Form<form_class>` instance has an :ref:`isValid()<form_is_valid>` method, which runs validation routines for
all its fields. When this method is called, if all fields contain valid data, it will:

- return **true**
- place the form's data in its :ref:`cleanedData<form_cleaned_data>` attribute.

The whole form, when rendered for the first time, will look like:

.. code-block:: html

    <label for="your_name">Your name: </label>
    <input id="your_name" type="text" name="your_name" maxlength="100" required />

Note that it does not include the <form> tags, or a submit button. We'll have to provide those ourselves in the template.

The Logic
---------

Form data is sent back to your controller, generally the same controller that published the form.
This allows us to reuse some of the same logic.

To handle the form we need to instantiate it in the controller for the URL where we want it to be published.

.. code-block:: php

    public function commentform()
    {
        if ($_SERVER['REQUEST_METHOD'] === "POST"):

            $form = new CommentForm(['data' => $_POST]);
            if ($form->isValid()):
                // process the data in form.cleaned_data as required
                // ...
                // redirect to a new URL:
            endif;
        else:
            $form = new CommentForm();
        endif;

        return render('create.html', ['form' => $form]);
    }

If we arrive at this controller with a **GET** request, it will create an empty form instance and pass it in to the
template for rendering. This is what we can expect to happen the first time we visit the URL.

If the form is submitted using a **POST** request, the controller will once again create a form instance and populate
it with data from the request: ``$form = new CommentForm(['data' => $_POST])``.
This is called "binding data to the form" (it is now a bound form).

We call the form's :ref:`isValid()<form_is_valid>` method; if it's not **true**, we go back to the template with the
form. This time the form is no longer empty (unbound) so the HTML form will be populated with the data previously
submitted, where it can be edited and corrected as required.

If :ref:`isValid()<form_is_valid>` is **true**, we'll now be able to find all the validated form data in its
:ref:`cleanedData<form_cleaned_data>` attribute. We can use this data to update the database or do other processing
before sending an HTTP redirect to the browser telling it where to go next.


We don't need to do much in our **create.html** template. The simplest example is:

.. code-block:: html

    <form method="post" novalidate>

        <?php echo $form;?>

        <input type="submit" value="Send" name="Send">
    </form>

All the form's fields and their attributes will be unpacked into HTML markup from that ``echo $form;``

.. note:: HTML5 input types and browser validation

    If your form includes a :ref:`URLField<form_urlfield>`, an :ref:`EmailField<form_emailfield>` or any integer field
    type, Powerform will use the url, email and number HTML5 input types. By default, browsers may apply their own
    validation on these fields, which may be stricter than Powerforms's validation. If you would like to disable this
    behavior, set the **novalidate** attribute on the form tag, or specify a different widget on the field, like
    TextInput.

That's all you need to get started, but the forms puts a lot more at your fingertips. Once you understand the basics of
the process described above, you should be prepared to understand other features of the forms system and ready to learn
a bit more about the underlying machinery.

More about Powerform classes
----------------------------

All form classes are created as subclasses of ``\Eddmash\PowerOrm\Form\Form``, including the
:doc:`ModelForm<modelform>`.

.. note:: **Models and Forms**

    In fact if your form is going to be used to directly add or edit a Powerorm model, a :doc:`ModelForm<modelform>` can
    save you a great deal of time, effort, and code, because it will build a form, along with the appropriate fields and
    their attributes, from a **Model** class.

Bound and unbound form instances
--------------------------------

The distinction between :ref:`Bound and unbound<form_bound_and_unbound>` forms is important:

- An **unbound form** has no data associated with it. When rendered to the user, it will be empty or will contain
  default values.
- A **bound form** has submitted data, and hence can be used to tell if that data is valid. If an invalid bound form is
  rendered, it can include inline error messages telling the user what data to correct.

The form's :ref:`isBound<form_is_bound>` attribute will tell you whether a form has data bound to it or not.

More on fields
--------------

Consider a more useful form than our minimal example above, which we could use to implement "contact me" functionality
on a personal website:

.. code-block:: php

    namespace App\Forms;

    use Eddmash\PowerOrm\Form\Form;

    class ContactForm extends Form
    {
        /**
         * @inheritDoc
         */
        public function fields()
        {
            return [
                'subject' => Form::CharField(['maxLength'=>100]),
                'message' => Form::CharField(['widget'=>Form::TextArea()]),
                'email' => Form::EmailField(),
                'cc_myself'=>Form::BooleanField(['required'=>false])
            ];
        }

    }

Our earlier form used a single field, **your_name**, a :ref:`CharField<form_charfield>`. In this case, our form has
four fields: **subject**, **message**, **sender** and **cc_myself**. :ref:`CharField<form_charfield>`,
:ref:`EmailField<form_emailfield>` and :ref:`BooleanField<form_booleanfield>` are just three of the available field
types; a full list can be found in :doc:`Form fields<fields>`.

Widgets
-------

Each :doc:`Form fields<fields>` has a corresponding :doc:`Widget class<widgets>`, which in turn corresponds to an HTML
form widget such as **<input type="text">**.

In most cases, the field will have a sensible default widget. For example, by default,
a :ref:`CharField<form_charfield>` will have a :ref:`TextInput<textinput_widget>` widget, that produces an 
**<input type="text">** in the HTML. If you needed **<textarea>** instead, you'd specify the appropriate widget when
defining your form field, as we have done for the message field.

Field data
----------

Whatever the data submitted with a form, once it has been successfully validated by calling **isValid()**
(and **isValid()** has returned **true**), the validated form data will be in the :ref:`cleanedData<form_cleaned_data>`
associative array.

This data will have been nicely converted into Php types for you.

.. note::

    You can still access the unvalidated data directly from **$_POST** at this point, but the validated data is better.

In the contact form example above, cc_myself will be a **boolean** value. Likewise, fields such as **IntegerField** and
**DecimalField** convert values to a Php **int** and **float** respectively.

Here's how the form data could be processed in the view that handles this form:

.. code-block:: php

    // on your controller
    public function contactform()
    {
        if (($_SERVER['REQUEST_METHOD'] === "POST"):

            $form = new ContactForm(['data' => $_POST]);
            if ($form->isValid()):
                $subject = $form->cleanedData['subject'];
                $email = $form->cleanedData['email'];
                $message = $form->cleanedData['message'];
                $cc_myself = $form->cleanedData['cc_myself'];

                // more code
            endif;
        else:
            $form = new ContactForm();
        endif;

        return render('form', ['form' => $form]);
    }

Some field types need some extra handling. For example, files that are uploaded using a form need to be handled
differently (they can be retrieved from **$_FILES**, rather than **$_POST**).

For details of how to handle file uploads with your form, see :ref:`Binding uploaded files<form_binding_uploaded_field>`
to a form.

Working with form templates
---------------------------

All you need to do to display your form, is to create an instance of the form and **echo** it out.

.. code-block:: php

    echo $form;

This will render its <label> and <input> elements appropriately.

Form rendering options
----------------------

.. note:: **Additional form template furniture**

    Don't forget that a form's output does not include the surrounding <form> tags, or the form's submit control.
    You will have to provide these yourself.

There are other output options though for the **<label>/<input>** pairs:

- **asTable()** will render them as table cells wrapped in **<tr>** tags
- **asParagraph()** will render them wrapped in **<p>** tags
- **asUl()** will render them wrapped in **<li>** tags

Note that you'll have to provide the surrounding **<table>** or **<ul>** elements yourself.

Here's the output of **asParagraph()** for our ContactForm instance:

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

Note that each form field has an ID attribute set to **id_<field-name>**, which is referenced by the accompanying label
tag. This is important in ensuring that forms are accessible to assistive technology such as screen reader software.
You can also :ref:`customize the way in which labels and ids are generated<form_configure_id_label>`.

See :ref:`Outputting forms as HTML<output_form_as_html>` for more on this.

Rendering fields manually
-------------------------

We can do it manually if we like (allowing us to reorder the fields, for example). Each field is available as an
attribute of the form using

.. code-block:: php

    echo $form->{name_of_field}

For example:

.. code-block:: html

    <form method="post" novalidate>

        <?= $form->nonFieldErrors(); ?>

        <div class="fieldWrapper">
            <?= $form->subject->getErrors(); ?>
            <label for="<?= $form->subject->getIdForLabel(); ?>">Email subject:</label>
            <?= $form->subject; ?>
        </div>

        <div class="fieldWrapper">
            <?= $form->message->getErrors(); ?>
            <label for="<?= $form->message->getIdForLabel(); ?>">Message:</label>
            <?= $form->message; ?>
        </div>

        <div class="fieldWrapper">
            <?= $form->email->getErrors(); ?>
            <label for="<?= $form->email->getIdForLabel(); ?>">Your email address:</label>
            <?= $form->email; ?>
        </div>

        <div class="fieldWrapper">
            <?= $form->cc_myself->getErrors(); ?>
            <label for="<?= $form->cc_myself->getIdForLabel(); ?>">CC yourself?:</label>
            <?= $form->cc_myself; ?>
        </div>

        <input type="submit" value="Send" name="Send">
    </form>

Complete **<label>** elements can also be generated using the **labelTag()**. For example:

.. code-block:: html

    <div class="fieldWrapper">
        <?= $form->cc_myself->getErrors(); ?>
        <?= $form->cc_myself->labelTag(); ?>
        <?= $form->cc_myself; ?>
    </div>

Rendering form error messages
-----------------------------

Of course, the price of this flexibility is more work. Until now we haven't had to worry about how to display form
errors, because that's taken care of for us. In this example we have had to make sure we take care of any errors for
each field and any errors for the form as a whole. Note **nonFieldErrors()** at the top of the
form and the **getErrors()** on each field.

Using ``$form->field_name->getErrors();`` displays a list of form errors, rendered as an unordered list.

This might look like:

.. code-block:: html

    <ul class="errorlist">
        <li>Sender is required.</li>
    </ul>

The list has a CSS class of **errorlist** to allow you to style its appearance. If you wish to further customize the
display of errors you can do so by looping over them:

.. code-block:: html

    <div class="fieldWrapper">
        <ol>
            <?php foreach ($form->subject->getErrors() as $error) : ?>
                <?= $error; ?>
            <?php endforeach; ?>
        </ol>
        <label for="<?= $form->subject->getIdForLabel(); ?>">Email subject:</label>
        <?= $form->subject; ?>
    </div>

Non-field errors (and/or hidden field errors that are rendered at the top of the form when using helpers like
**form.asParagraph()**) will be rendered with an additional class of nonfield to help distinguish them from
field-specific errors.

Looping over the form's fields
------------------------------

If you're using the same HTML for each of your form fields, you can reduce duplicate code by looping through each field
in turn using a **foreach** loop:

.. code-block:: html

    <?php foreach ($form as $field):?>
        <div class="fieldWrapper">
            <ol>
                <?php foreach ($field->getErrors() as $error) : ?>
                    <?= $error; ?>
                <?php endforeach; ?>
            </ol>
            <label for="<?= $field->getIdForLabel(); ?>"><?=$field->getLabelName()?></label>
            <?= $field; ?>
        </div>
    <?php endforeach; ?>

Useful attributes and methods on **Field** include:

- **getLabelName()**

  The label of the field, e.g. Email address.

- **labelTag()**
    The field's label wrapped in the appropriate HTML <label> tag. This includes the form's label_suffix. For example,
    the default label_suffix is a colon:

    .. code-block:: html

        <label for="id_email">Email address:</label>

- **getIdForLabel()**

    The ID that will be used for this field (id_email in the example above). If you are constructing the label manually,
    you may want to use this in lieu of **labelTag()**. It's also useful, for example, if you have some inline
    JavaScript and want to avoid hardcoding the field's ID.

- **value()**

    The value of the field. e.g someone@example.com.

- **getHtmlName()**

    The name of the field that will be used in the input element's name field. This takes the form prefix into account,
    if it has been set.

- **getHelpText()**

    Any help text that has been associated with the field.


- **getErrors()**

    Outputs a <ul class="errorlist"> containing any validation errors corresponding to this field. You can customize the
    presentation of the errors with a **foreach** loop as shown above.
    In this case, each object in the loop is a simple string containing the error message.

- **isHidden()**

    This method is **true** if the form field is a **hidden** field and **false** otherwise.

    .. code-block:: php

        foreach ($form as $field):
            if($field->isHidden()):
                // do something
            endif;
        endforeach;

Looping over hidden and visible fields
--------------------------------------

If you're manually laying out a form, you might want to treat **<input type="hidden">** fields differently from 
non-hidden fields. 

For example, because hidden fields don't display anything, putting error messages "next to" the field could cause
confusion for your users – so errors for those fields should be handled differently.

Powerform provides two methods on a form that allow you to loop over the hidden and visible fields independently:

- **hiddenFields()** and
- **visibleFields()**.

Here's a modification of an earlier example that uses these two methods:

.. code-block:: html

    // display hidden fields
    <?php foreach ($form->hiddenFields() as $field): ?>
        <?= $field; ?>
    <?php endforeach; ?>

    // display visible fields
    <?php foreach ($form->visibleFields() as $field): ?>

        <div class="fieldWrapper">
            <?=$field->getErrors()?>
            <?=$field->labelTag()?>
            <?= $field; ?>
        </div>
    <?php endforeach; ?>

This example does not handle any errors in the hidden fields. Usually, an error in a hidden field is a sign of form
tampering, since normal form interaction won't alter them. However, you could easily insert some error displays for
those form errors, as well.