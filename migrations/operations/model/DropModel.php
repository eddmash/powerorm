<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 3:29 PM
 */

namespace powerorm\migrations\operations\model;
use powerorm\migrations\operations\Operation;
use powerorm\migrations\ProjectState;


/**
 * Class DropModel
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DropModel extends Operation{

    public function __construct($opts=[]){

        parent::__construct($opts);
        $this->model_name = $opts['model'];
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $model = $current_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $model)):
            $connection->schema_editor->drop_model($model);
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $model = $desired_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $model)):
            $connection->schema_editor->create_model($model);
        endif;
    }

    public function describe()
    {
        return sprintf("drop_%s", $this->model_name);
    }

    public function update_state(ProjectState $state)
    {
        $state->remove_model($this->model_name);
    }

}