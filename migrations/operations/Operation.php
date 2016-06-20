<?php

namespace powerorm\migrations\operations;

use powerorm\DeConstruct;
use powerorm\migrations\ProjectState;
use powerorm\Object;

/**
 * Class Operation
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Operation extends Object implements DeConstruct
{
    private $constructor_args;

    public function __construct($opts=[]){

        $this->constructor_args = func_get_args();
    }

    /**
     * @param $connection
     * @param ProjectState $current_state the state of the project before the operation is applied
     * @param ProjectState $desired_state the state of the project after operation is applied
     * @return mixed
     */
    public abstract function update_database($connection, $current_state, $desired_state);

    public abstract function rollback_database($connection, $current_state, $desired_state);

    public abstract function describe();

    /**
     * Updates the state based on what this operation needs done.
     * @param ProjectState $state
     * @return mixed
     */
    public abstract function update_state(ProjectState $state);
    
    /**
     * @inheritdoc
     * @return array
     */
    public function constructor_args()
    {
        return $this->constructor_args;
    }

    public function skeleton(){
        return [
            'path'=>get_class($this),
            'constructor_args'=> $this->constructor_args(),
        ];
    }
    
    public function allow_migrate_model($connection, $model){

        return $model->meta->can_migrate();
    }
}