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

Field types
-----------

The generated Form class will have a form field for every model field specified, in the order specified in the fields
attribute.

Each model field has a corresponding default form field. For example, a :ref:`CharField<model_charfield>` on a model is
represented as a :ref:`CharField<form_charfield>` on a form. A model ManyToManyField is represented as a MultipleChoiceField.

Here is the full list of conversions:

=========================================  ============================================================================
 Model Field                                 Form Field
=========================================  ============================================================================
:ref:`AutoField<model_autofield>`           Not represented in the form
:ref:`CharField<model_charfield>`           :ref:`CharField<form_charfield>`
:ref:`BooleanField<model_booleanfield>`     :ref:`BooleanField<form_booleanfield>`
:ref:`UrlField<model_urlfield>`             :ref:`UrlField<form_urlfield>`
:ref:`DateField<model_datefield>`           :ref:`DateField<form_datefield>`
:ref:`EmailField<model_emailfield>`         :ref:`EmailField<form_emailfield>`
:ref:`DecimalField<model_decimalfield>`     :ref:`DecimalField<form_decimalfield>`
:ref:`ImageField<model_imagefield>`         :ref:`ImageField<form_imagefield>`
:ref:`IntegerField<model_integerfield>`     :ref:`IntegerField<form_integerfield>`
:ref:`SlugField<model_slugfield>`           :ref:`SlugField<form_slugfield>`
:ref:`TextField<model_textfield>`           :ref:`TextField<form_textfield>`
:ref:`ForeignKey<model_foreignkey>`         :ref:`ModelChoiceField <form_modelchoicefield>` (see below)
:ref:`ManyToMany<many_to_many_field>`       :ref:`ModelMultipleChoiceField<form_modelmultiplechoicefield>` (see below)

=========================================  ============================================================================

As you might expect, the :ref:`ForeignKey<model_foreignkey>`  and :ref:`ForeignKey<model_foreignkey>`  model field types
are special cases:

:ref:`ForeignKey<model_foreignkey>` is represented by :ref:`ModelChoiceField <form_modelchoicefield>`, which is a
:ref:`ChoiceField <form_choicefield>` whose choices are a model :doc:`Queryset </orm/queries/queryset>`.

:ref:`ManyToMany<many_to_many_field>`  is represented by :ref:`ModelMultipleChoiceField<form_modelmultiplechoicefield>`,
which is a :ref:`MultipleChoiceField<form_multiplechoicefield>` whose choices are a model
:doc:`Queryset </orm/queries/queryset>`.

In addition, each generated form field has attributes set as follows:

- If the model field has **blank=true**, then **required** is set to **false** on the form field. Otherwise,
  **required=true**.
- The form field's **label** is set to the **verboseName** of the model field, with the first character capitalized.
- The form field's **helpText** is set to the **helpText** of the model field.
- If the model field has **choices** set, then the form field's **widget** will be set to **Select**, with choices
  coming from the model field's **choices**. The choices will normally include the blank choice which is selected by
  default. If the field is required, this forces the user to make a selection. The blank choice will not be included
  if the model field has **blank=false** and an explicit default value (the **default** value will be initially
  selected instead).

Finally, note that you can override the form field used for a given model field. See Overriding the default fields below.

.. _overriding_the_default_fields:

Overriding the default fields
-----------------------------

The default field types, as described in the Field types table above, are sensible defaults. If you have a **DateField**
in your model, chances are you'd want that to be represented as a **DateField** in your form. But **ModelForm** gives
you the flexibility of changing the form field for a given model.

To specify a custom widget for a field, use the **widgets()** method of **ModelForm**class. This should be a associative
array mapping field names to widget classes or instances.

For example, if you want the **CharField** for the name attribute of Author to be represented by a **<textarea>**
instead of its default **<input type="text">**, you can override the field's widget:

.. code-block:: php

    namespace App\Forms;

    use App\Models\Author;
    use Eddmash\PowerOrm\Form\Form;
    use Eddmash\PowerOrm\Form\ModelForm;

    class AuthorForm extends ModelForm
    {
        protected $modelFields = ['name', 'email'];
        protected $modelClass = 'App\Models\Author';

        /**
         * @inheritDoc
         */
        public function widgets()
        {
            return [
                'name'=>Form::TextArea(['cols'=>80, 'rows'=>20])
            ];
        }


    }

The widgets() method returns an associatie array with field name as key and either widget
instances (e.g., **Textarea(...)**).

Similarly, you can specify the **labels**, **helpTexts** and **errorMessages** methods if you want to further customize
a field.

You can also specify **fieldClasses** to customize the type of fields instantiated by the form.For example, if you
wanted to use **MySlugFormField** for the slug fieldFor example, if you wanted to use **MySlugFormField** for the slug
field

For example if you wanted to customize the wording of all user facing strings for the name field:

.. code-block:: php

    namespace App\Forms;

    use App\Models\Author;
    use Eddmash\PowerOrm\Form\Form;
    use Eddmash\PowerOrm\Form\ModelForm;

    class AuthorForm extends ModelForm
    {
        protected $modelFields = ['name', 'email', 'content', 'reporter', 'slug'];
        protected $modelClass = 'App\Models\Author';

        /**
         * @inheritDoc
         */
        public function widgets()
        {
            return [
                'name'=>Form::TextArea(['cols'=>80, 'rows'=>20])
            ];
        }

        /**
         * @inheritDoc
         */
        public function labels()
        {
            return [
                'name'=>"Your Name"
            ];
        }

        /**
         * @inheritDoc
         */
        public function helpTexts()
        {
            return [
                'name'=>"whats your name ?"
            ];
        }

        /**
         * @inheritDoc
         */
        public function fieldClasses()
        {
            return [
                'slug'=> MySlugField::class
            ];
        }
    }

Finally, if you want complete control over of a field – including its type, validators, required, etc. – you can do this 
by declaratively specifying fields like you would in a regular **Form**.

If you want to specify a field's validators, you can do so by defining the field declaratively and setting its
**validators** parameter:

.. code-block:: php

    use App\Models\Author;
    use Eddmash\PowerOrm\Form\Form;
    use Eddmash\PowerOrm\Form\ModelForm;
    use Eddmash\PowerOrm\Form\Validations\SlugValidator;

    class AuthorForm extends ModelForm
    {
        protected $modelFields = ['name', 'email', 'content', 'reporter', 'slug'];
        protected $modelClass = 'App\Models\Author';

        /**
         * @inheritDoc
         */
        public function fields()
        {
            return [
                'slug'=>Form::CharField(['validators'=>[SlugValidator::instance()]])
            ];
        }
    }

.. note::

    When you explicitly instantiate a form field like this, it is important to understand how **ModelForm** and regular
    **Form** are related.

    **ModelForm** is a regular **Form** which can automatically generate certain fields. The fields that are
    automatically generated depend on the content returns by **fields()** method on which fields have already been
    defined declaratively. Basically, **ModelForm** will only generate fields that are **missing** from the form, or in 
    other words, fields that weren't defined declaratively.

    Fields defined declaratively are left as-is, therefore any customizations made by any of the methods shown above
    such as **widgets**, **labels**, **helpTexts**, or **errorMessages** are ignored; these only apply to fields that
    are generated automatically.

    Similarly, fields defined declaratively do not draw their attributes like **maxLength** or **required** from the
    corresponding model. If you want to maintain the behavior specified in the model, you must set the relevant
    arguments explicitly when declaring the form field.

Providing initial values
------------------------

As with regular forms, it's possible to specify initial data for forms by specifying an initial parameter when
instantiating the form. Initial values provided this way will override both initial values from the form field and
values from an attached model instance. For example:

.. code-block:: php

    $article = Article::objects()->get(['pk'=>1]);
    echo $article->headline;
    'My headline'
    $form = new ArticleForm(['initial'=>['headline'=> 'Initial headline'], 'instance'=>$article]);
    echo $form['headline']->value();
    'Initial headline'

.. _overriding_the_clean_method:

Overriding the clean() method
-----------------------------

You can override the **clean()** method on a model form to provide additional validation in the same way you can on a
normal form.

A model form instance attached to a model object will contain an **modelInstance** attribute that gives its methods access
to that specific model instance.


The save() method
-----------------

Every ModelForm also has a save() method. This method creates and saves a database object from the data bound to the 
form. A subclass of ModelForm can accept an existing model instance as the keyword argument instance; if this is 
supplied, save() will update that instance. If it's not supplied, save() will create a new instance of the specified
model:

.. code-block:: php

    //Create a form instance from POST data
    $form = new AuthorForm(['data'=>$_POST]);
    $form->save(false);

    // Create a form to edit an existing Article, but use
    //  POST data to populate the form.
    $a = Article::objects()->get(['pk'=>1]);

    $form = new AuthorForm(['data'=>$_POST, 'instance'=>$a]);
    $form->save(false);

Note that if the form hasn't been validated, calling **save()** will do so by checking **form.errors()**.
A **ValueError** will be raised if the data in the form doesn't validate – i.e., if **form.errors()** evaluates to
**true**.