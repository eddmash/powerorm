Form and field validation
#########################

.. contents::
    :local:
    :depth: 2

Form validation happens when the data is cleaned. If you want to customize this process, there are various places to
make changes, each one serving a different purpose. Three types of cleaning methods are run during form processing.
These are normally executed when you call the **isValid()** method on a form.
There are other things that can also trigger cleaning and validation (accessing the **errors()** method or
calling **fullClean()** directly), but normally they won't be needed.

In general, any cleaning method can raise **ValidationError** if there is a problem with the data it is processing,
passing the relevant information to the **ValidationError** constructor. :ref:`See below<raising_validation_error>` for
the best practice in raising **ValidationError**. If no **ValidationError** is raised, the method should return the
cleaned (normalized) data as a PHP object.

Most validation can be done using :ref:`validators<using_validator>` - simple helpers that can be reused easily.
Validators are objects that take a single argument when invoked like functions and raise ValidationError on invalid input.
Validators are run after the field's **toPhp** and **validate** methods have been called.

Validation of a form is split into several steps, which can be customized or overridden:

    - The **toPhp()** method on a **Field** is the first step in every validation. It coerces the value to a correct
      datatype and raises **ValidationError** if that is not possible. This method
      accepts the raw value from the widget and returns the converted value. For example, a **DateField** will turn the
      data into a Php DateTime or raise a **ValidationError**.

    - The **validate()** method on a **Field** handles field-specific validation that is not suitable for a validator.
      It takes a value that has been coerced to a correct datatype and raises **ValidationError** on any error.
      This method does not return anything and shouldn't alter the value. You should override it to handle validation
      logic that you can't or don't want to put in a validator.

    - The **runValidators()** method on a **Field** runs all of the field's validators and aggregates all the errors
      into a single **ValidationError**. You shouldn't need to override this method.

    - The **clean()** method on a **Field** subclass is responsible for running **toPhp()**, **validate()**, and
      **runValidators()** in the correct order and propagating their errors. If, at any time, any of the methods raise
      **ValidationError**, the validation stops and that error is raised. This method returns the clean data, which is
      then inserted into the **cleanedData** associative array of the form.

    - The **clean<fieldname>()** method is called on a form subclass – where <fieldname> is replaced with the name of
      the form field attribute. This method does any cleaning that is specific to that particular attribute,
      unrelated to the type of field that it is. This method is not passed any parameters. You will need to look up
      the value of the field in **$this->cleanedData** and remember that it will be a Php object at this point, not
      the original string submitted in the form (it will be in **cleanedData** because the general field **clean()**
      method, above, has already cleaned
      the data once).

      For example, if you wanted to validate that the contents of a **CharField** called **serialnumber** was unique,
      cleanSerialnumber() would be the right place to do this. You don't need a specific field (it's just a CharField),
      but you want a formfield-specific piece of validation and, possibly, cleaning/normalizing the data.

      The return value of this method replaces the existing value in **cleanedData**, so it must be the field's value
      from **cleanedData** (even if this method didn't change it) or a new cleaned value.

    - The form subclass's **clean()** method can perform validation that requires access to multiple form fields. This
      is where you might put in checks such as "if field A is supplied, field B must contain a valid email address".
      This method can return a completely different associative array if it wishes, which will be used as
      the **cleanedData**.

    Since the field validation methods have been run by the time **clean()** is called, you also have access to the
    form's **errors** attribute which contains all the errors raised by cleaning of individual fields.

    Note that any errors raised by your **Form.clean()** override will not be associated with any field in particular.
    They go into a special "field" (called **__all__**), which you can access via
    the :ref:`nonFieldErrors() <non_field_errors>` method if you need to. If you want to attach errors to a specific
    field in the form, you need to call :ref:`addError()<form_add_error>`.

    Also note that there are special considerations when overriding the **clean()** method of a ModelForm subclass.
    (see the :ref:`ModelForm documentation<overriding_the_clean_method>` for more information)

These methods are run in the order given above, one field at a time. That is, for each field in the form (in the order
they are declared in the form definition), the **Field.clean()** method (or its override) is run,
then **clean<fieldname>()**.Finally, once those two methods are run for every field, the **Form.clean()** method, or
its override, is executed whether or not the previous methods have raised errors.

Examples of each of these methods are provided below.

As mentioned, any of these methods can raise a **ValidationError**. For any field, if the **Field.clean()** method
raises a **ValidationError**, any field-specific cleaning method is not called. However, the cleaning methods for all
remaining fields are still executed.

.. _raising_validation_error:

Raising ValidationError
-----------------------

In order to make error messages flexible and easy to override, consider the following guidelines:

Provide a descriptive error code to the constructor:

.. code-block:: php

    // Good
    ValidationError('Invalid value', 'invalid');

    // Bad
    ValidationError('Invalid value');

Putting it all together:

.. code-block:: php

    throw new ValidationError('Invalid value', 'invalid');

Following these guidelines is particularly necessary if you write reusable forms, form fields, and model fields.

While not recommended, if you are at the end of the validation chain (i.e. your form clean() method) and you know you
will never need to override your error message you can still opt for the less verbose:

.. _raising_multiple_errors:

Raising multiple errors
-----------------------

If you detect multiple errors during a cleaning method and wish to signal all of them to the form submitter, it is
possible to pass a list of errors to the **ValidationError** constructor.

As above, it is recommended to pass a list of **ValidationError** instances with codes and params but a list of strings
will also work:

.. code-block:: php

    // Good
    throw new ValidationError([
        ValidationError('Error 1', 'error1'),
        ValidationError('Error 2', 'error2'),
    ])

    // Bad
    throw new ValidationError([
        _('Error 1'),
        _('Error 2'),
    ])

.. _using_validator:

Using validation in practice
----------------------------

The previous sections explained how validation works in general for forms. Since it can sometimes be easier to put
things into place by seeing each feature in use, here are a series of small examples that use each of the previous
features.

Using validators
................

Powerform's form (and model) fields support use of simple utility classes known as validators. A validator is merely
a callable object that takes a value and simply returns nothing if the value is valid or throws a **ValidationError** if
not. These can be passed to a field's constructor, via the field's validators argument, or defined on the Field class
itself with the **getDefaultValidators()** method.

Simple validators can be used to validate values inside the field, let's have a look at Powerform's SlugField:

.. code-block:: php

    class SlugField extends CharField
    {
        /**
         * @inheritDoc
         */
        public function getDefaultValidators()
        {
            $validators = parent::getDefaultValidators();
            $validators[] = SlugValidator::instance();
            return $validators;
        }

    }

As you can see, **SlugField** is just a **CharField** with a customized validator that validates that submitted text
obeys to some character rules. This can also be done on field definition so:


.. code-block:: php

    $slug = Form::SlugField();

is equivalent to:

.. code-block:: php

    $slug = Form::CharField(['validators'=>[SlugValidator::instance()]]);


Form field default cleaning
...........................

Let's first create a custom form field that validates its input is a string containing comma-separated email addresses.
The full class looks like this:

.. code-block:: php

    namespace App\Forms;


    use Eddmash\PowerOrm\Form\Fields\Field;
    use Eddmash\PowerOrm\Form\Validations\EmailValidator;

    class MultiEmailField extends Field
    {
        public function toPhp($value)
        {
            if (empty($value)) :
                return [];
            endif;

            return explode(",", $value);
        }

        /**
         * @inheritDoc
         */
        public function validate($value)
        {
            foreach ($value as $item) :
                $validator = EmailValidator::instance();
                $validator($item);
            endforeach;
        }

    }

Every form that uses this field will have these methods run before anything else can be done with the field's data.
This is cleaning that is specific to this type of field, regardless of how it is subsequently used.

Let's create a simple ContactForm to demonstrate how you'd use this field:

.. code-block:: php

    class ContactForm extends Form
    {
        public function fields()
        {
            return [
                'subject' => Form::CharField(['maxLength'=>100]),
                'recipients' => MultiEmailField::instance(),
                'cc_myself' => Form::BooleanField(['required' => false]),
            ];
        }
    }

Simply use **MultiEmailField** like any other form field. When the **isValid()** method is called on the form, the
**MultiEmailField.clean()** method will be run as part of the cleaning process and it will, in turn, call the custom
**toPhp()** and **validate()** methods.

Cleaning a specific field attribute
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Continuing on from the previous example, suppose that in our **ContactForm**, we want to make sure that the recipients
field always contains the address **"fred@example.com"**. This is validation that is specific to our form, so we don't
want to put it into the general **MultiEmailField** class. Instead, we write a cleaning method that operates on the
recipients field, like so:

.. code-block:: php

    class ContactForm extends Form
    {
        public function fields()
        {
            return [
                'subject' => Form::CharField(['maxLength'=>100]),
                'recipients' => MultiEmailField::instance(),
                'cc_myself' => Form::BooleanField(['required' => false]),
            ];
        }

        public function cleanRecipients()
        {
            $data = $this->cleanedData['recipients'];
            if (!in_array("fred@example.com", $data)) :
                throw new ValidationError("You have forgotten about Fred!");
            endif;
            return $data;
        }
    }

.. _validating_fields_with_clean:

Cleaning and validating fields that depend on each other
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Suppose we add another requirement to our contact form: if the **cc_myself** field is **true**, the subject must
contain the word **"help"**. We are performing validation on more than one field at a time, so the
form's **clean()** method is a good spot to do this. Notice that we are talking about the **clean()** method on the form
here, whereas earlier we were writing a **clean()** method on a field. It's important to keep the field and form
difference clear when working out where to validate things. Fields are single data points, forms are a collection of
fields.

By the time the form's **clean()** method is called, all the individual field clean methods will have been run 
(the previous two sections), so **$this->cleanedData** will be populated with any data that has survived so far. So you
also need to remember to allow for the fact that the fields you are wanting to validate might not have survived the
initial individual field checks.

There are two ways to report any errors from this step. Probably the most common method is to display the error at the
top of the form. To create such an error, you can raise a **ValidationError** from the **clean()** method. For example:

.. code-block:: php

    class ContactForm extends Form
    {
        // .. everything before

        public function clean()
        {
            parent::clean();

            if (array_key_exists('cc_myself', $this->cleanedData) &&
                array_key_exists('recipients', $this->cleanedData) &&
                array_key_exists('subject', $this->cleanedData)
            ) :
                $ccMyself = $this->cleanedData['cc_myself'];
                $recipients = $this->cleanedData['recipients'];
                $subject = $this->cleanedData['subject'];
                if ($ccMyself && $recipients) :

                    if (!strlen(strstr($subject, 'help'))) :
                        throw new ValidationError(
                            "Did not send for 'help' in the subject despite CC'ing yourself."
                        );
                    endif;
                endif;
            endif;
        }
    }

In this code, if the validation error is raised, the form will display an error message at the top of the form
(normally) describing the problem.

The call to **parent::clean()** in the example code ensures that any validation logic in parent classes is
maintained. use **$this->cleanedData** to access cleaned field data.

The second approach for reporting validation errors might involve assigning the error message to one of the fields. 
In this case, let's assign an error message to both the "subject" and "cc_myself" rows in the form display.
Be careful when doing this in practice, since it can lead to confusing form output. We're showing what is possible here
and leaving it up to you and your designers to work out what works effectively in your particular situation.
Our new code (replacing the previous sample) looks like this:


.. code-block:: php

    class ContactForm extends Form
    {
        // .. everything before

        public function clean()
        {
            parent::clean();

            if (array_key_exists('cc_myself', $this->cleanedData) &&
                array_key_exists('recipients', $this->cleanedData) &&
                array_key_exists('subject', $this->cleanedData)
            ) :
                $ccMyself = $this->cleanedData['cc_myself'];
                $recipients = $this->cleanedData['recipients'];
                $subject = $this->cleanedData['subject'];
                if ($ccMyself && $recipients) :

                    if (!strlen(strstr($subject, 'help'))) :
                        $msg = "Did not send for 'help' in the subject despite CC'ing yourself.";
                        $this->addError("cc_myself", $msg);
                        $this->addError("subject", $msg);
                    endif;
                endif;
            endif;
        }
    }

The second argument of :ref:`addError()<form_add_error>` can be a simple string, or preferably an instance of
**ValidationError**. See :ref:`Raising Validation errors<raising_validation_error>` for more details. Note that
:ref:`addError()<form_add_error>` automatically removes the field from **cleanedData**.