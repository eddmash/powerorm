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

use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * Alters a field's database column (e.g. null, max_length) to the provided new field.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
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
    public $preserveDefault = false;

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
        if (false === $this->preserveDefault):
            $field = $this->field->deepClone();
            $field->default = NOT_PROVIDED;
        else:
            $field = $this->field;
        endif;

        $fields = $state->modelStates[$this->modelName]->fields;
        $newFields = [];
        foreach ($fields as $name => $ofield) :
            if ($name == $this->name):
                $newFields[$name] = $field;
            else:
                $newFields[$name] = $ofield;
            endif;
        endforeach;
        $state->modelStates[$this->modelName]->fields = $newFields;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {

        $this->_alterField($schemaEditor, $fromState, $toState);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        $this->_alterField($schemaEditor, $fromState, $toState);
    }

    /**
     * Does the actual field alteration.
     *
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _alterField($schemaEditor, $fromState, $toState)
    {
        $toModel = $toState->getRegistry()->getModel($this->modelName);
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)):
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $fromField = $fromModel->meta->getField($this->name);
            $toField = $toModel->meta->getField($this->name);
            if (false === $this->preserveDefault):
                $toField->default = $this->field->default;
            endif;
            $schemaEditor->alterField($fromModel, $fromField, $toField);

            if (false === $this->preserveDefault):
                $toField->default = NOT_PROVIDED;
            endif;
        endif;
    }

}
