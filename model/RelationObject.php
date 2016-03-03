<?php
namespace powerorm\model;
/**
 * Act a buffer for Relation Fields, to help avoid issues with
 * Class Relation
 */
class RelationObject{

    public $model_name;
    public $model;
    public $_ci;

    public function __construct($model_name){
        $this->model_name = $model_name;
        $this->_ci = & get_instance();
    }

    public function _model_object(){
        if(!isset($this->model)):
            $this->_ci->load->model($this->model_name);
            $this->model =  $this->_ci->{$this->model_name};
        endif;
    }

    public function __get($key){
        $this->_model_object();
        return $this->model->{$key};

    }
    public function __call($method, $args){
        $this->_model_object();

        if(empty($args)):
            // invoke from the queryset
            return call_user_func(array($this->model, $method));
        else:
            // invoke from the queryset
            if(is_array($args)):
                return call_user_func_array(array($this->model, $method), $args);
            else:
                return call_user_func(array($this->model, $method), $args);
            endif;
        endif;

    }
    public function __set($key, $value){
        $this->_model_object();
        $this->model->{$key} = $value;
    }

    public function __toString(){
        return sprintf("Related Model: %s", $this->model_name);
    }
}