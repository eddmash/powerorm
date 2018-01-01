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
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 * Alters a field's database column (e.g. null, max_length) to the provided new field.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AlterField extends FieldOperation
{
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
    public function updateState(ProjectState $state)
    {
        if (false === $this->preserveDefault):
            $alteredField = $this->field->deepClone();
            $alteredField->default = NOT_PROVIDED;
        else:
            $alteredField = $this->field;
        endif;

        $fields = $state->getModelState($this->modelName)->fields;
        $newFields = [];

        foreach ($fields as $name => $oldField) :
            if ($name == $this->name):
                $newFields[$name] = $alteredField;
            else:
                $newFields[$name] = $oldField;
            endif;
        endforeach;
        $state->getModelState($this->modelName)->fields = $newFields;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $this->alterField($schemaEditor, $fromState, $toState);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $this->alterField($schemaEditor, $fromState, $toState);
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
     *
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @throws \Eddmash\PowerOrm\Exception\ValueError
     */
    private function alterField($schemaEditor, $fromState, $toState)
    {
        $toModel = $toState->getRegistry()->getModel($this->modelName);
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)):
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $fromField = $fromModel->getMeta()->getField($this->name);
            $toField = $toModel->getMeta()->getField($this->name);
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
