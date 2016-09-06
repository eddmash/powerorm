<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 3:30 PM.
 */
namespace powerorm\migrations\operations\model;

use powerorm\migrations\operations\Operation;
use powerorm\migrations\ProjectState;

/**
 * Class RenameModel.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RenameModel extends Operation
{
    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->old_name = $opts['old_name'];
        $this->new_name = $opts['new_name'];
    }

    public function update_state(ProjectState $state)
    {
        // map new name to the model state before rename
        $state->models[$this->new_name] = $state->models[$this->old_name];

        // change name to new name
        $state->models[$this->new_name]->name = $this->new_name;

        // remove the model state before rename
        $state->remove_model($this->old_name);
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        // alter table name
        // alter relation fields name
        // alter m2m
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        //repeat update with different names
    }

    public function describe()
    {
        return sprintf('rename_%1$s_to_%2$s', $this->old_name, $this->new_name);
    }
}
