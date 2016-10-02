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

class RenameField extends Operation
{
    public $modelName;
    public $newName;
    public $oldName;

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        $fields = $state->modelStates[$this->modelName]->fields;
        $fieldsNew = [];
        foreach ($fields as $name => $field) :
            if ($name == $this->oldName):
                $fieldsNew[$this->newName] = $field;
            else:
                $fieldsNew[$name] = $field;
            endif;
        endforeach;
        $state->modelStates[$this->modelName]->fields = $fieldsNew;

    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Rename field %s on %s to %s', $this->oldName, $this->modelName, $this->newName);
    }

}
