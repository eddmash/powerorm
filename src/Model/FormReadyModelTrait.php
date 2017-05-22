<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\Exception\ValidationError;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * Ensures the model is ready for use with powerorm forms.
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
trait FormReadyModelTrait
{
    /**
     * Calls cleanFields, clean on the model and throws a ``ValidationError`` for any errors that occurred.
     *
     * @param array $exclude
     *
     * @throws ValidationError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function fullClean($exclude = [])
    {
        $errors = [];

        try{
            $this->cleanFields($exclude);
        }catch (ValidationError $error){
            //todo
        }

        try{
            $this->clean();
        }catch (ValidationError $error){
            //todo
        }
        if (!empty($errors)) :
            throw new ValidationError($errors);
        endif;
    }

    public function clean()
    {

    }

    /**
     * Cleans all fields and throw a ValidationError containing an associtive array of all validation errors if any
     * occur.
     *
     * @param array $exclude
     *
     * @throws ValidationError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function cleanFields($exclude = [])
    {
        $errors = [];

        /** @var $field Field */
        foreach ($this->meta->getConcreteFields() as $field) :
            if (in_array($field->getName(), $exclude)) :
                continue;
            endif;
            $value = $this->{$field->getAttrName()};
            // Skip validation for empty fields with blank=True. The developer
            // is responsible for making sure they have a valid value.
            if ($field->formBlank && empty($value)) :
                continue;
            endif;

            try{
                $this->{$field->getAttrName()} = $field->clean($this, $value);
            }catch (ValidationError $error){
                $errors[$field->getName()] = $error->getErrorList();
            }
        endforeach;

        if (!empty($errors)) :
            throw new ValidationError($errors);
        endif;
    }
}
