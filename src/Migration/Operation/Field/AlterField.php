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

class AlterField extends Operation
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
     * @var bool
     */
    public $preserveDefault;

    /**
     * @var Field
     */
    public $field;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Alter field %s on %s', $this->name, $this->modelName);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        if(false == $this->preserveDefault):
            $field = $this->field->deepClone();
            $field->default = NOT_PROVIDED;
        else:
            $field = $this->field;
        endif;

        $fields = $state->modelStates[$this->modelName]->fields;
        $newFields = [];
        foreach ($fields as $name => $ofield) :
            if($name == $this->name):
                $newFields[$name] = $field;
            else:
                $newFields[$name] = $ofield;
            endif;
        endforeach;
        $state->modelStates[$this->modelName]->fields = $newFields;
    }

}
