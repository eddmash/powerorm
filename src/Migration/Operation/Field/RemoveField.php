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
 * Removes a field from a model.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RemoveField extends FieldOperation
{
    /**
     * {@inheritdoc}
     */
    public function updateState(ProjectState $state)
    {
        $fields = $state->getModelState($this->modelName)->fields;

        $fieldsNew = [];
        foreach ($fields as $name => $field) {
            if ($name !== $this->name) {
                $fieldsNew[$name] = $field;
            }
        }
        $state->getModelState($this->modelName)->fields = $fieldsNew;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Remove field %s from %s', $this->name, $this->modelName);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(
        SchemaEditor $schemaEditor,
        ProjectState $fromState,
        ProjectState $toState
    ) {
        $fromModel = $fromState->getRegistry()->getModel($this->modelName);

        if ($this->allowMigrateModel($schemaEditor->connection, $fromModel)) {
            $schemaEditor->removeField(
                $fromModel,
                $fromModel->getMeta()->getField($this->name)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards(
        SchemaEditor $schemaEditor,
        ProjectState $fromState,
        ProjectState $toState
    ) {
        $toModel = $toState->getRegistry()->getModel($this->modelName);
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)) {
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $schemaEditor->addField(
                $fromModel,
                $toModel->getMeta()->getField($this->name)
            );
        }
    }
}
