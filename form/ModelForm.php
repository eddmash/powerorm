<?php
namespace powerorm\form;

use powerorm\form\FormField;
use powerorm\form\FormException;
use powerorm\model\OrmExceptions;

/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 2/16/16
 * Time: 11:27 AM
 */
class Form{
    protected $_ci;
    public $fields=[];
    public $validator;
    protected $ready = FALSE;

    public function __construct(){
        $this->_ci =& get_instance();
        $this->_ci->load->library('form_validation');

        // use the global form_validation object
        $this->validator = $this->_ci->form_validation;
    }

    public function _load_model($model_name){
        $model_name = ucwords(strtolower($model_name));
        if(!class_exists($model_name, FALSE)):
            $this->_ci->load->model($model_name);
        endif;
    }

    public function _validate_field($opts){
        if(!isset($opts['type'])):
            throw new ValueError("field `%s` doesnt have value type set", $opts['name']);
        endif;
    }

    public function fields($fields){
        if(empty($fields)):
            return FALSE;
        endif;

        foreach ($fields as $field_name=>$field_value) :
            $field_value['name'] = $field_name;

            // ensure required arguments are present
            $this->_validate_field($field_value);

            $this->fields[$field_name] = new FormField($field_value);
        endforeach;
        return TRUE;
    }

    public function create(){
        foreach ($this->fields as $field_name => $field_value) :
            $this->fields[$field_name] = $field_value;
        endforeach;

        $this->ready = TRUE;
    }

    public function validation_rules(){
        if($this->ready === FALSE):
            throw new FormException("Trying to validate a non-existent form, did you call the create() method ?");
        endif;

        // cycle through the fields setting there validation rules
        foreach ($this->fields as $field) :

            $this->validator->set_rules($field->name, $field->name, $field->validation_rules());

        endforeach;
    }

    public function validate(){
        // load all the field validations
        $this->validation_rules();

        $this->_populate_fields();

        // run the validations
        return $this->validator->run();


    }

    public function _populate_fields(){
        $post = $this->_ci->input->post();

        foreach ($this->fields as $field_name=>$field_obj) :
            if(!array_key_exists($field_name,$post)):
                continue;
            endif;
            $field_obj->set_value($post[$field_name]);
        endforeach;
    }

    public function is_valid(){
        return $this->validate();
    }

    public function _errors(){
        $validation_object =&_get_validation_object();
        return $validation_object->error_array();
    }

    public function errors(){
        return implode("\n", $this->_errors());
    }

    public function __get($field_name){

        if(array_key_exists($field_name, $this->fields)):
            return $this->fields[$field_name];
        endif;
    }

    public function __set($field_name, $field_value){

        if(array_key_exists($field_name, $this->fields)):
            $this->fields[$field_name]->set_value($field_value);
        endif;
    }

}

class ModelForm extends Form{

    public $model_object;
    public $fields;
    public $none_model_fields;

    private $only;
    private $ignored;

    public function __construct($context){
        parent::__construct();
        $this->model_object = $context;
    }

    public function ignore($fields_names){
        if($this->only):
            throw new OrmExceptions('setting only() and ignore() is not allowed on the same form');
        endif;

        if(!is_array($fields_names)):
            throw new ValueError("ignore() expects an array of model field names to show");
        endif;

        $model_fields = $this->model_object->meta->fields;
        foreach ($model_fields as $field_name=>$field_obj) :

            if(in_array($field_name, $fields_names)):
                continue;
            endif;

            $this->fields[$field_name] = $model_fields[$field_name]->form_field();
        endforeach;
        $this->ignored = TRUE;
    }

    public function only($fields_names){
        if($this->ignored):
            throw new \OrmExceptions('setting only() and ignore() is not allowed on the same form');
        endif;

        if(!is_array($fields_names)):
            throw new ValueError("field() expects an array of model field names to show");
        endif;

        foreach ($fields_names as $field_name) :
            $this->fields[$field_name] = $this->model_object->meta->fields[$field_name]->form_field();
        endforeach;

        $this->only = TRUE;
    }

    /**
     * Add to the form fields
     * @param $fields
     * @return bool
     */
    public function custom_fields($fields){
        if(empty($fields)):
            return FALSE;
        endif;

        foreach ($fields as $field_name=>$field_value) :
            $field_value['name'] = $field_name;

            // ensure required arguments are present
            $this->_validate_field($field_value);

            $this->fields[$field_name] = new FormField($field_value);
        endforeach;
        return TRUE;
    }

    public function create(){

        // load all the fields in the model
        if(empty($this->fields)):

            foreach($this->model_object->meta->fields as $field_name=>$field_value):
                $this->fields[$field_name] = $field_value->form_field();
            endforeach;

        endif;

        parent::create();
    }

    public function initial(){

    }

    public function _no(){

    }

    public function _form_fields(){

    }

    public function _from_model(){

        foreach ($this->model_object->meta->fields as $field_name=>$field_obj) :
            $this->fields[$field_name] = $field_obj->form_field();
        endforeach;

    }

    public function __toString(){
        return sprintf('< %s Form >', ucwords(strtolower($this->model_object->meta->model_name)));
    }
}