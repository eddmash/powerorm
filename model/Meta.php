<?php
namespace powerorm\model;

/**
 * Class Meta
 * @package powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Meta{
    public $model_name;
    public $db_table;
    public $primary_key;
    public $fields = [];
    public $relations_fields = [];
    public $local_fields = [];
    public $inverse_fields = [];

    public function load_field($field_obj){
        $this->fields[$field_obj->name] = $field_obj;
        $this->set_pk($field_obj);

        if(is_subclass_of($field_obj, 'RelatedField')):
            $this->relations_fields[$field_obj->name] = $field_obj;
        else:
            $this->local_fields[$field_obj->name] = $field_obj;
        endif;
    }

    public function load_inverse_field($field_obj){
        $this->inverse_fields[$field_obj->name] = $field_obj;
    }

    public function get_field($field_name){
        return (array_key_exists($field_name, $this->fields))? $this->fields[$field_name]: NULL;
    }

    public function set_pk($field_obj){
        if($field_obj->primary_key):
            $this->primary_key = $field_obj;
        endif;
    }

    public function fields_names(){
        return array_keys($this->fields);
    }
}