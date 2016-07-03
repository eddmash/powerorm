<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 4/17/16
 * Time: 10:30 AM
 */

namespace powerorm\migrations\operations;


use powerorm\migrations\ModelState;
use powerorm\migrations\ProjectState;

/**
 * Class CreateModel
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CreateModel extends Operation
{
    public $model_name;
    public $fields;
    public $depends_on;
    public $options;

    public function __construct($opts=[]){

        parent::__construct($opts);
        $this->model_name = $opts['model'];
        $this->fields = $opts['fields'];
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $model = $desired_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $model)):

            $connection->create_model($model);
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $model = $current_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $model)):
            $connection->drop_model($model);
        endif;
    }

    public function describe()
    {
        return sprintf("add_%s", $this->model_name);
    }
    
    public function update_state(ProjectState $state){
        $model_state = new ModelState($this->model_name, $this->fields);
        $state->add_model($model_state);
    }

}

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
            $connection->drop_model($model);
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $model = $desired_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $model)):
            $connection->create_model($model);
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

/**
 * Class RenameModel
 * @package powerorm\migrations\operations
 * @since 1.0.2
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RenameModel extends Operation{

    public function __construct($opts=[]){

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