<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation\Field;

use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * Adds a field to a model.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AddField extends Operation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $modelName;
    /**
     * @var Field
     */
    public $field;

    /**
     * @var bool
     */
    public $preserveDefault;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Add field %s to %s', $this->name, $this->modelName);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        if (!$this->preserveDefault):
            $field = $this->field->deepClone();
            $field->default = NOT_PROVIDED;
        else:
            $field = $this->field;
        endif;

        $state->modelStates[$this->modelName]->fields[$this->name] = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $constArgs = parent::getConstructorArgs();
        if (false === $this->preserveDefault):
            unset($constArgs['preserveDefault']);
        endif;

        return $constArgs;
    }

}
