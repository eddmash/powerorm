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

use Eddmash\PowerOrm\Backends\SchemaEditor;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * Adds a field to a model.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AddField extends FieldOperation
{
    /**
     * @var bool
     */
    public $preserveDefault = true;

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
    public function updateState(ProjectState $state)
    {
        // remove default if preserveDefault==false,
        // we dont want it in future updates.

        if (false === $this->preserveDefault) {
            $field = $this->field->deepClone();
            $field->default = NOT_PROVIDED;
        } else {
            $field = $this->field;
        }
        $state->getModelState($this->modelName)->fields[$this->name] = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(
        SchemaEditor $schemaEditor,
        ProjectState $fromState,
        ProjectState $toState
    ) {
        $toModel = $toState->getRegistry()->getModel($this->modelName);

        /* @var $field Field */
        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)) {
            $fromModel = $fromState->getRegistry()->getModel($this->modelName);
            $field = $toModel->getMeta()->getField($this->name);
            if (false === $this->preserveDefault) {
                $field->default = $this->field->default;
            }

            $schemaEditor->addField($fromModel, $field);

            if (false === $this->preserveDefault) {
                $field->default = NOT_PROVIDED;
            }
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
    public function getConstructorArgs()
    {
        $constArgs = parent::getConstructorArgs();

        if (true === $this->preserveDefault) {
            unset($constArgs['preserveDefault']);
        }

        return $constArgs;
    }

    public function __toString()
    {
        return sprintf(
            '%s <%s:%s>',
            $this->getFullClassName(),
            $this->modelName,
            $this->name
        );
    }
}
