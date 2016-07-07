<?php

namespace powerorm\migrations\operations;

use powerorm\DeConstruct;
use powerorm\helpers\Strings;
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
        $path = '';

        if(Strings::starts_with($this->full_class_name(), 'powerorm\migrations\operations')):
            $path = 'powerorm\migrations\operations as operation';
        endif;

        return [
            'name'=>sprintf('operation\%s', $this->get_class_name()),
            'path'=> $path,
            'full_name'=> $this->full_class_name(),
            'constructor_args'=> $this->constructor_args(),
        ];
    }
    
    public function allow_migrate_model($connection, $model){

        return $model->meta->can_migrate();
    }

    public static function CreateModel($opts){
        return new CreateModel($opts);
    }

    public static function RenameModel($opts){
        return new RenameModel($opts);
    }

    public static function DropModel($opts){
        return new DropModel($opts);
    }

    public static function AddField($opts){
        return new AddField($opts);
    }

    public static function DropField($opts){
        return new DropField($opts);
    }
    public static function AlterField($opts){
        return new AlterField($opts);
    }

    public static function RenameField($opts){
        return new RenameField($opts);
    }
}