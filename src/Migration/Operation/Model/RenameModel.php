<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Migration\Operation\Operation;

class RenameModel extends ModelOperation
{
    public $oldName;
    public $newName;

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        // model state
        $modelState = $state->modelStates[$this->oldName];
        // change name of model to the new name
        $modelState->name = $this->newName;

        // map the model to the new name
        $state->modelStates[$this->newName] = $modelState;
        // remove the mapping that uses the old name.
        $state->removeModelState($this->oldName);

        //todo take care of relationships
    }

    public function getDescription()
    {
        return sprintf('Rename model %s to %s', $this->oldName, $this->newName);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        parent::databaseForwards($schemaEditor, $fromState, $toState); // TODO: Change the autogenerated stub
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        parent::databaseBackwards($schemaEditor, $fromState, $toState); // TODO: Change the autogenerated stub
    }
}
