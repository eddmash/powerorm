<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

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
}
