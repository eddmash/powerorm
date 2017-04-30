Creating forms from models
##########################

.. contents::
    :local:
    :depth: 2

.. _model_form_class:

ModelForm
---------

If you're building a database-driven app, chances are you'll have forms that map closely to Powerorm models. For
instance, you might have a BlogComment model, and you want to create a form that lets people submit comments.
In this case, it would be redundant to define the field types in your form, because you've already defined the fields
in your model. we will be using the :ref:`Author model<author_model_example>`.

For this reason, Powerform provides a helper class that lets you create a Form class from a Powerorm model.

For example:


.. code-block:: php

    namespace App\Forms;


    use Eddmash\PowerOrm\Form\Form;
    use Eddmash\PowerOrm\Form\ModelForm;
    use Eddmash\PowerOrm\Form\Validations\MaxValueValidator;
    use Eddmash\PowerOrm\Form\Widgets\DateInput;
    use Eddmash\PowerOrm\Form\Widgets\EmailInput;
    use Eddmash\PowerOrm\Form\Widgets\NumberInput;
    use Eddmash\PowerOrm\Form\Widgets\TextInput;

    /**
     * Class AuthorForm
     * @package App\Forms
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    class AuthorForm extends ModelForm
    {
        protected $modelClass = 'App\Models\Author';
        protected $excludes = ['id'];
    }


.. _overriding_the_clean_method:

Overriding the clean() method
-----------------------------

You can override the **clean()** method on a model form to provide additional validation in the same way you can on a
normal form.

A model form instance attached to a model object will contain an **modelInstance** attribute that gives its methods access
to that specific model instance.