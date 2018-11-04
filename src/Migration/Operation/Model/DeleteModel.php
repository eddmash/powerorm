<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Backends\SchemaEditor;
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 *  Drops a model's table.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DeleteModel extends ModelOperation
{
    /**
     * {@inheritdoc}
     */
    public function updateState(ProjectState $state)
    {
        $state->removeModelState($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Delete model %s', $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $model = $fromState->getRegistry()->getModel($this->name);
        if ($this->allowMigrateModel($schemaEditor->connection, $model)) {
            $schemaEditor->deleteModel($model);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $model = $toState->getRegistry()->getModel($this->name);
        if ($this->allowMigrateModel($schemaEditor->connection, $model)) {
            $schemaEditor->createModel($model);
        }
    }
}
