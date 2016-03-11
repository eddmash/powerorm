<?php
namespace powerorm\form;

use powerorm\exceptions\FormException;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\ValueError;

/**
 * Class Form
 * @package powerorm\form
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
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

    /**
     * Gets a single field instance in the form fields array and returns it
     * @param $name
     * @param bool|FALSE $id
     * @return int|null
     */
    public function get_field($name){
        foreach ($this->fields as $field) :

            if($field->name == $name):
                return $field;
                break;
            endif;

        endforeach;
    }

    /**
     * Creates the form opening tag
     * @param string $action
     * @param array $attributes
     * @param array $hidden
     * @return string
     *
     */
    public function open($action = '', $attributes = array(), $hidden = array()){
        $this->_ci->load->helper('url');

        if(strlen($action)<=0):
            $action = current_url();
        endif;

        // create a multipart form or a normal form
        $form_open = '';
        if($this->_is_multipart()):
            $form_open = form_open_multipart($action, $attributes, $hidden);
        else:
            $form_open = form_open($action, $attributes, $hidden);
        endif;

        if(isset($this->_form_help)):
            $form_open .= "<p class='help-block form-help-text'>$this->_form_help</p>";
        endif;

        return $form_open;
    }

    /**
     * Create the form closing tags and displays any errors that have not been dispaly explicitly
     * @param string $extra
     * @return string
     */
    public function close($extra = ''){
        return form_close($extra);
    }

    /**
     * Return the HTML widget (input, radio, textarea e.t.c) for the specified field.
     * @param $name
     * @param array $args
     * @return mixed
     */
    public function field_widget($name, $args=array()){
        return $this->get_field($name)->widget($args);
    }

    /**
     * Returns the form label
     * @param $name
     * @param array $args
     * @return mixed
     */
    public function field_label($name, $args=array()){
        return $this->get_field($name)->label($args);
    }

    /**
     * Creates a form fieldset.
     * @param $legend_text
     * @param array $attrs
     * @return string
     */
    public function open_fieldset($legend_text, $attrs=array()){
        return form_fieldset($legend_text, $attrs);
    }

    /**
     * Closes a form fieldset
     * @param string $extra
     * @return string
     */
    public function close_fieldset($extra=''){
        return form_fieldset_close($extra);
    }

    /**
     * @ignore
     * Checks if a fields array has any field of type file or image and  prepares the form for uploading
     * @return bool
     */
    public function _is_multipart(){
        $is_multipart = FALSE;

        foreach ($this->fields as $field) :
            if($field->type=='file' || $field->type=='image'):
                $is_multipart = TRUE;

                // stop looping, there is no need to loop anymore
                // since we have found atleast one field with the required types
                break;

            endif;
        endforeach;

        // load the upload library
        $this->_ci->load->library('upload');

        $this->multipart = $is_multipart;
        return $is_multipart;

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

    public function form(){
        foreach ($this->fields as $field_name => $field_value) :
            $this->fields[$field_name] = $field_value;
        endforeach;

        $this->ready = TRUE;
        return $this;
    }

    public function validation_rules(){
        if($this->ready === FALSE):
            throw new FormException("Trying to validate a non-existent form, did you call the create() method ?");
        endif;

        // cycle through the fields setting there validation rules
        foreach ($this->fields as $field) :

            $this->validator->set_rules($field->get_widget_name(), $field->get_widget_name(), $field->validation_rules());

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
            $this->fields[$field_name] = $field_value;

        endif;
    }

    public function __isset($field){
        return $this->get_field($field);

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
        if(!is_array($fields_names)):
            throw new OrmExceptions(
                sprintf('setting ignore() expects an array of arguments but got a %s', gettype($fields_names)));
        endif;

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
            throw new OrmExceptions('setting only() and ignore() is not allowed on the same form');
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

    public function form(){

        // load all the fields in the model
        if(empty($this->fields)):

            foreach($this->model_object->meta->fields as $field_name=>$field_value):
                $this->fields[$field_name] = $field_value->form_field();
            endforeach;

        endif;

        return parent::form();
    }

    public function initial(){

    }
    /**
     * Saves the forms model_instance into the database
     * @param null $model_name
     * @param null $values
     * @return mixed
     */
    public function save(){
        //  update model instance fields with the form data
        foreach ($this->fields as $field):
            if(array_key_exists($field->name, get_object_vars($this->model_object))):
                $this->model_object->{$field->name} = $field->value;
            endif;
        endforeach;
        return $this->model_object->save();
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