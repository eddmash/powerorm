<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Backends\SchemaEditor;
use Eddmash\PowerOrm\Migration\State\ProjectState;

class RenameModel extends ModelOperation
{
    public $oldName;

    public $newName;

    /**
     * {@inheritdoc}
     */
    public function updateState(ProjectState $state)
    {
        // model state
        $modelState = $state->getModelState($this->oldName);
        // change name of model to the new name
        $modelState->name = $this->newName;

        // map the model to the new name
        $state->addModelState($modelState, $this->newName);
        // remove the mapping that uses the old name.
        $state->removeModelState($this->oldName);

        //todo take care of relationships
    }

    public function getDescription()
    {
        return sprintf('Rename model %s to %s', $this->oldName, $this->newName);
    }

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        // TODO: Implement databaseForwards() method.
    }

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        // TODO: Implement databaseBackwards() method.
    }
}
