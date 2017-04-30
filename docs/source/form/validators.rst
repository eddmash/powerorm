Validators
##########

Writing validators
------------------

A validator is a callable that takes a value and raises a **ValidationError** if it doesn't meet some criteria.
Validators can be useful for re-using validation logic between different types of fields.

For example, here's a validator that only allows even numbers:

.. code-block:: php

    function validate_even($value)
    {
        if ($value % 2 != 0):
            throw new ValidationError(sprintf('%s is not an even number', $value), 'invalid');
        endif;
    }

You can add this to a model field via the field's validators argument:

.. code-block:: php

    namespace App\Forms;

    use App\Models\Blog;
    use Eddmash\PowerOrm\Exception\ValidationError;
    use Eddmash\PowerOrm\Form\Form;
    use Eddmash\PowerOrm\Form\ModelForm;

    class BlogForm extends ModelForm
    {
        protected $modelFields = "__all__";
        /**
         * @inheritDoc
         */
        public function getModelClass()
        {
            return Blog::class;
        }

        /**
         * @inheritDoc
         */
        public function fields()
        {
            return [
              'name'=>Form::CharField(['validators'=>['App\Forms\validate_even']])
            ];
        }


    }

Because values are converted to php before validators are run, you can even use the same validator with forms:

.. code-block:: php

    namespace App\Forms;

    use Eddmash\PowerOrm\Exception\ValidationError;
    use Eddmash\PowerOrm\Form\Form;

    class CommentForm extends Form
    {
        /**
         * @inheritDoc
         */
        public function fields()
        {
            return [
                'name' => Form::CharField(),
                'url' => Form::UrlField(),
                'even_field'=>Form::IntegerField(['validators'=>['validate_even']]),
                'moderate' => Form::BooleanField(['required' => false]),
            ];
        }



    }

You can also use a class with a **__invoke()** method for more complex or configurable validators.

How validators are run
----------------------

See the :doc:`form validation<validations>` for more information on how validators are run in forms, and
Validating objects for how they're run in models. Note that validators will not be run automatically when you save a
model, but if you are using a ModelForm, it will run your validators on any fields that are included in your form.
See the :doc:`ModelForm<modelform>` documentation for information on how model validation interacts with forms.