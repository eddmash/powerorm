Model Form Example
##################

.. _author_model_example:

The Model Class
---------------

.. code-block:: php

    namespace App\Models;

    use Eddmash\PowerOrm\Model\Model;

    /**
    * Class Author
    */
    class Author extends Model
    {
        public function unboundFields()
        {
            return [
                'name'=>Model::CharField(['maxLength'=>25]),
                'date'=>Model::DateField(),
            ];
        }

    }

The form that represents the :ref:`Author Model<author_model_example>`

.. _author_form_example:

The Model Form Class
--------------------

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

        public function fields()
        {
            return [
                'date2' => Form::DateField(
                    [
                        'widget' => DateInput::instance(['class' => 'form-control']),
                        'required'=>false
                    ]
                ),
                'age' => Form::IntegerField(
                    [
                        'validators'=>[MaxValueValidator::instance(['max'=>10])],
                        'required'=>false,
                        'widget' => NumberInput::instance(['class' => 'form-control']),
                        'helpText'=>"What is your age"
                    ]
                ),
                'email' => Form::EmailField([
                    'required'=>false,
                    'widget' => EmailInput::instance(['class' => 'form-control']),
                ]),
            ];
        }

        public function widgets()
        {
            return [
                'date' => DateInput::instance(['class' => 'form-control']),
                'name' => TextInput::instance(['class' => 'form-control']),
            ];
        }
    }

.. _rendering_on_template:


The template
------------

.. code-block:: php

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <?php
    /**@var $form \Eddmash\PowerOrm\Form\Form */
    ?>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <form method="post" action="" enctype="multipart/form-data" novalidate>
                    <?=$form;?>
                    <input type="submit" value="Send" name="Send">
                </form>
            </div>
        </div>
    </div>

.. _manual_field_rendering:


The Rendering fields manually
-----------------------------

.. code-block:: php

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <?php
    /**@var $form \Eddmash\PowerOrm\Form\Form */
    ?>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <form method="post" action="" enctype="multipart/form-data" novalidate>

                    <?php
                    dump($form->nonFieldErrors());
                    /**@var $field \Eddmash\PowerOrm\Form\Fields\Field */
                    foreach ($form as $field):?>
                        <div class='form-group'>
                            <label for='"<?= $field->getIdForLabel(); ?>'><?= $field->getLabelName(); ?></label>
                            <?= $field; ?>
                            <?= $field->getHelpText(); ?>
                        </div>
                    <?php endforeach;
                    ?>


                    <input type="submit" value="Send" name="Send">
                </form>
            </div>
        </div>
    </div>