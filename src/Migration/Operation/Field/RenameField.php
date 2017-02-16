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

/**
 * Renames a field on the model. Might affect db_column too.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RenameField extends FieldOperation
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
            if ($name === $this->oldName):
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

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        $toModel = $toState->getRegistry()->getModel($this->modelName);
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)):
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $schemaEditor->alterField(
                $fromModel,
                $fromModel->meta->getField($this->oldName),
                $toModel->meta->getField($this->newName)
            );
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        $toModel = $toState->getRegistry()->getModel($this->modelName);
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)):
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $schemaEditor->alterField(
                $fromModel,
                $fromModel->meta->getField($this->newName),
                $toModel->meta->getField($this->oldName)
            );
        endif;
    }
}
