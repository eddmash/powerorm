<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\Exception\ValidationError;
use Eddmash\PowerOrm\Form\Fields\TypedChoiceField;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Model;

/**
 * This trait makes it possible for model fields to easily be used on model forms.
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
trait FormFieldReadyTrait
{
    public $validators = [];

    /**
     * Returns an Eddmash\PowerOrm\Form\Fields\Field instance that represents this database field.
     *
     * @param array $kwargs
     *
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function formField($kwargs = [])
    {
        $fieldClass = ArrayHelper::pop(
            $kwargs,
            'fieldClass',
            \Eddmash\PowerOrm\Form\Fields\CharField::class
        );

        $defaults = [
            'required' => !$this->formBlank,
            'label' => $this->verboseName,
            'helpText' => $this->helpText,
        ];

        if ($this->hasDefault()):
            $defaults['initial'] = $this->getDefault();
        endif;

        if ($this->choices):
            $include_blank = true;

            if ($this->formBlank || empty($this->hasDefault()) || !in_array('initial', $kwargs)):
                $include_blank = false;
            endif;

            $defaults['choices'] = $this->getChoices(['include_blank' => $include_blank]);
            $defaults['coerce'] = [$this, 'toPhp'];

            $fieldClass = ArrayHelper::getValue(
                $kwargs,
                'formChoicesClass',
                TypedChoiceField::class
            );

        endif;

        $defaults = array_merge($defaults, $kwargs);

        return $fieldClass::instance($defaults);
    }

    /**
     * Set value of the field on the from as received from the form.
     *
     * @param Model $model
     * @param $value
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function saveFromForm(Model $model, $value)
    {
        $model->{$this->name} = $value;
    }

    public function clean(Model $model, $value)
    {
        $value = $this->toPhp($value);
        $this->validate($model, $value);
        $this->runValidators($value);

        return $value;
    }

    /**
     * Convert the value to a php object the field understands.
     * throws ValidationError of conversion is not possible.
     *
     * @param $value
     *
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function toPhp($value)
    {
        return $value;
    }

    /**
     * Validates value and throws ValidationError. Subclasses should override this to provide validation logic.
     *
     * @param Model $model
     * @param $value
     *
     * @throws ValidationError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function validate(Model $model, $value)
    {
        if (!empty($this->choices) && !empty($value)) :
            foreach ($this->choices as $key => $choice) :
                if (is_array($choice)) :
                    foreach ($choice as $inkey => $inchoice) :
                        if ($value === $inchoice) :
                            return;
                        endif;
                    endforeach;
                else:
                    if ($value === $choice) :
                        return;
                    endif;
                endif;
            endforeach;
            throw new ValidationError(
                sprintf('Value %s is not a valid choice.', $value),
                'invalid_choice'
            );
        endif;

        if (is_null($value) && !$this->isNull()) :
            throw new ValidationError('This field cannot be null.', 'null');
        endif;

        if (empty($value) && !$this->formBlank) :
            throw new ValidationError('This field cannot be blank.', 'blank');
        endif;
    }

    /**
     * Returns the validators to be applied on this field. this returns both getDefaultValidators() and getValidators().
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    private function getFieldValidators()
    {
        return array_merge($this->getDefaultValidators(), $this->getValidators());
    }

    /**
     * Returns the default validators to be applied on this field.
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getDefaultValidators()
    {
        return [];
    }

    /**
     * Returns the default validators to be applied on this field.
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * Passes the value through all the validators for this field.
     *
     * @param $value
     *
     * @throws ValidationError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function runValidators($value)
    {
        if (empty($value)):
            return;
        endif;

        // collect all validation errors for this field
        $validationErrors = [];
        foreach ($this->getFieldValidators() as $validator) :

            try {
                $validator($value);
            } catch (ValidationError $error) {
                $validationErrors = array_merge($validationErrors, $error->getErrorList());
            }
        endforeach;

        if (!empty($validationErrors)):
            throw new ValidationError($validationErrors);
        endif;
    }
}
