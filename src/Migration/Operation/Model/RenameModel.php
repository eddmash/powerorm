<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16
 * Time: 2:09 PM.
 */
namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Migration\Operation\Operation;

class RenameModel extends Operation
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

        //todo take care of relatinships

    }

    public function getDescription()
    {
        return sprintf('Rename model %s to %s', $this->oldName, $this->newName);
    }

}
