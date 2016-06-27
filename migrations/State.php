<?php

namespace powerorm\migrations;

use powerorm\BaseOrm;
use powerorm\model\BaseModel;
use powerorm\model\field\InverseRelation;
use powerorm\Object;
use powerorm\registry\App;

/**
 * Class StateApps
 * @package powerorm\migrations
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class StateApps extends App{

    public function __construct($model_states=[]){
        $this->model_states = $model_states;
    }

    public function _get_models(){


        if(!empty($this->model_states)):

            foreach ($this->model_states as $name=>$state) :
                $state->to_model($this);
            endforeach;

        endif;
        return $this->all_models;
    }
}

/**
 * Represents the state of the project at any particular time.
 *
 * This state can be passed around for use.
 *
 * @package powerorm\migrations
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ProjectState extends Object{

    public $models;

    public function __construct($models=[], $options=[]){
        $this->models = $models;
    }

    public function add_model(ModelState $model_state){
        $model_name = $model_state->name;
        $this->models[$model_name] = $model_state;
    }

    public function remove_model($model_name){
        $model_name = $this->lower_case($model_name);

        if(isset($this->models[$model_name])):
            // remove model state from the project state
            unset($this->models[$model_name]);

            // remove model from the registy
            $this->registry()->unregister_model($model_name);
        endif;
    }

    public function get_model($name){
        $name = $this->lower_case($name);

        if(!array_key_exists($name, $this->models)):
            return NULL;
        endif;

        return $this->models[$name];
    }
    
    public static function from_apps(){
        $app_models = BaseOrm::instance()->get_registry()->get_models();
        $models = [];
        foreach ($app_models as $name=>$model) :
            $models[$name] = ModelState::from_model($model);
        endforeach;

        return new ProjectState($models);
    }

    /**
     * Create a new registry based on the present models in the state.
     * @return StateApps
     */
    public function registry(){

        return new StateApps($this->models);
    }

    public function deep_clone(){

        $models = [];
        foreach ($this->models as $name=>$model) :
            $models[$name] = $model->deep_clone();
        endforeach;
        return new static($models);
    }
}

/**
 * This represents a model in the application.
 * This way we are able to make alterations to the model without affecting the actual models.
 *
 * @package powerorm\migrations
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ModelState extends Object{
    public $name;
    public $fields = [];

    public function __construct($name, $fields, $options=[]){
        $this->name = $this->lower_case($name);
        $this->fields = $fields;
    }

    /**
     * Creates a model state for the model passed in.
     * @param BaseModel $model
     * @param bool|TRUE $with_relations
     * @return ModelState
     */
    public static function from_model(BaseModel $model, $with_relations = TRUE){
        $fields = [];

        foreach ($model->meta->local_fields as $name=>$field) :
            $fields[$name] = $field;
        endforeach;

        if($with_relations):
            foreach ($model->meta->relations_fields as $name=>$field) :

                // ignore the inverse fields
                if($field instanceof InverseRelation):
                    continue;
                endif;

                $fields[$name] = $field;
            endforeach;
        endif;

        return new ModelState($model->meta->model_name, $fields);
    }
    
    public function to_model(App $registry){

        $model = $this->_define_load_class($this->name);

        // load model with fields
        $fields = $this->fields;

        $model->init($registry, $fields);

        return $model;
    }

    public function _define_load_class($class_name){
        $class_name = ucfirst($class_name);
        // we create a new namespace and define new classes because,
        // we might be dealing with a model that has been dropped
        // Meaning if we try to load the model using the normal codeigniter way,
        // we will get and error of model does not exist
        $class = 'namespace powerorm\migrations\_fake_\models;

            class %1$s extends \PModel{

                 public function fields(){}
            }';

        if(!class_exists('powerorm\migrations\_fake_\models\\'.$class_name, FALSE)):
            eval(sprintf($class, $class_name));
        endif;


        $class_name = '\powerorm\migrations\_fake_\models\\'.$class_name;

        return new $class_name();
    }

    public function deep_clone(){

        $fields = [];
        foreach ($this->fields as $name=>$field) :
            $fields[$name] = $field->deep_clone();
        endforeach;

        return new static($this->name, $fields);
    }

}