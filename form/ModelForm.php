<?php
namespace powerorm\form;

use PModel;
use powerorm\exceptions\DuplicateField;
use powerorm\exceptions\FormException;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\ValueError;

/**
 * A class for generating forms based on the models.
 *
 * We will be using the example below for explanation.
 *
 * <h4><strong>Using form in the Controller</strong></h4>
 *
 * Once the model has been load, you can pass it to the view like and other variable
 * <pre><code>class User_controller extends CI_Controller{
 *          public function login(){
 *              $form_builder = $this->user_model->form_builder();
 *              $form_builder->only(['username', 'password']);
 *              $form_builder->customize([
 *                          'username'=>['attrs'=>['placeholder'=>'username.....']],
 *                          'password'=>['type'=>'password',
 *                                          'attrs'=>['placeholder'=>'password...']]
 *              ]);
 *              $form = $form_builder->form();
 *
 *              // check model is valid
 *              if($form->is_valid()){
 *                  $username = $form->username;
 *                  $password = $form->password;
 *
 *                  // authorize user with there credentials
 *                  // check that use was authorized
 *                  if($this->auth->login($username, $password)){
 *                      // redirect to set_page
 *                      redirect('profile');
 *                  }
 *              }
 *
 *              $data['form'] = $form;
 *              $data['page_title']= "Welcome Back";
 *              $this->load->view('auth/login', $data);
 *          }
 *
 * }</code></pre>
 *
 *
 *
 * <h4><strong>Using the Form on View</strong></h4>
 *
 * The form class provides several methods that can be used to display the generated form.
 *
 * The simplest way to load the form is to loop trough all the fields present in the form. as shown below
 *
 * <pre><code>$form->open();
 *      foreach($form->fields as $field):
 *          $field->errors();
 *          $field->label();
 *          $field->widget(array("class"=>"form-control"));
 *      endforeach;
 * $form->close();</code></pre>
 *
 * Or accessing each field individually from the form itself; as shown below:
 *
 * <pre><code>$form->open();
 *          $form->label('username');
 *          $form->widget('username', ["class"=>"form-control"]);
 *          $form->label('password');
 *          $form->widget('password', ["class"=>"form-control"]);
 *          $form->label('age');
 *          $form->widget('age', ["class"=>"form-control"]);
 * $form->close();</code></pre>
 *
 * @package powerorm\form
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Form{
    /**
     * @ignore
     * @var \CI_Controller
     */
    protected $_ci;

    /**
     * Holds the fields on this form.
     * @var array
     */
    public $fields=[];

    /**
     * @ignore
     * @var bool
     */
    protected $ready = FALSE;

    /**
     * @ignore
     * @var string
     */
    protected $form_message = '';

    /**
     * @ignore
     * @var
     */
    public $_is_multipart;

    /**
     * @ignore
     * @var array
     */
    public $initial;

    /**
     * @ignore
     * @param array $initial
     */
    public function __construct($initial=[]){
        $this->_ci =& get_instance();
        $this->initial = $initial;
    }

    /**
     * Gets a single field instance in the form fields array and returns it
     * <h4>Usage</h4>
     * if a form has a fields username, you get the field object:
     *
     * <pre><code>$form->get_field('username);</code></pre>
     *
     * @param $field_name
     * @return string
     */
    public function get_field($field_name){

        return (array_key_exists($this->_stable_name($field_name), $this->fields))? $this->fields[$this->_stable_name($field_name)] : '';
    }

    /**
     * @internal
     */
    public function _load_libs(){
        $this->_ci->load->library('form_validation');
        $this->_ci->load->helper('url');

        if($this->_is_multipart()):
            // load the upload library
            $this->_ci->load->library('upload');
        endif;

    }

    /**
     * Creates the form opening tag.
     * <h4>Usage</h4>
     * @param string $action
     * @param array $attributes
     * @param array $hidden
     * @return string
     *
     */
    public function open($action = '', $attributes = array(), $hidden = array()){

        if(strlen($action)<=0):
            $action = current_url();
        endif;

        // create a multipart form or a normal form
        $form_open = '';
        if($this->_is_multipart):
            $form_open = form_open_multipart($action, $attributes, $hidden);
        else:
            $form_open = form_open($action, $attributes, $hidden);
        endif;

        if(isset($this->form_message)):
            $form_open .= "<p class='help-block form-help-text'>$this->form_message</p>";
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

        if(!empty($this->fields)):
            foreach ($this->fields as $field) :
                if($field->type=='file' || $field->type=='image'):
                    $is_multipart = TRUE;

                    // stop looping, there is no need to loop anymore
                    // since we have found atleast one field with the required types
                    break;

                endif;
            endforeach;
        endif;
        $this->_is_multipart = $is_multipart;
        return $is_multipart;

    }

    /**
     * @internal
     * @param $model_name
     */
    public function _load_model($model_name){
        $model_name = ucwords(strtolower($model_name));
        if(!class_exists($model_name, FALSE)):
            $this->_ci->load->model($model_name);
        endif;
    }

    /**
     * @internal
     * @param $opts
     * @throws ValueError
     */
    public function _validate_field($opts){

        if(!isset($opts['type'])):
            throw new ValueError(sprintf("Please provide the { type } for the field `%s`", $opts['name']));
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

            $this->fields[$this->_stable_name($field_name)] = new FormField($field_value);
        endforeach;
    }

    public function form(){
        foreach ($this->fields as $field_name => $field_value) :
            $this->fields[$this->_stable_name($field_name)] = $field_value;
        endforeach;

        $this->ready = TRUE;

        // load data to form
        if(!empty($this->initial)):

            foreach ($this->initial as $name=>$value) :

                if(!isset($this->{$name})):
                    continue;
                endif;

                if(is_object($value) && $value instanceof Queryset):
                    $value = $value->value();
                endif;

                if(is_object($value) && $value instanceof PModel):
                    $pk_field =$value->meta->primary_key->name;
                    $value = $value->{$pk_field};
                endif;

                $this->{$name} = $value;

            endforeach;
        endif;

        $this->_load_libs();

        return $this;
    }

    public function initial($data=[]){
        if(!is_array($data)):
            throw new \InvalidArgumentException('The initial data should be an associative array, of fieldname and value');
        endif;

        foreach ($data as $key=>$value) :
            if(array_key_exists($key, $this->initial)):
                $this->initial[$key] = $value;
            endif;
        endforeach;
    }

    public function help_text($message){
        if(is_string($message)):
            $this->form_message = ucfirst($message);
        endif;
    }

    /**
     * @ignore
     * @throws FormException
     */
    public function validation_rules(){
        if($this->ready === FALSE):
            throw new FormException("Trying to validate a non-existent form, did you call the form() method ?");
        endif;

        // cycle through the fields setting there validation rules
        foreach ($this->fields as $field) :
            $this->_ci->form_validation->set_rules(
                $field->get_widget_name(),
                $field->get_widget_name(),
                $field->validation_rules());
        endforeach;

    }

    /**
     * @ignore
     * @return mixed
     * @throws FormException
     */
    public function validate(){
        // load all the field validations
        $this->validation_rules();

        $this->_populate_fields();

        // run the validations
        return $this->_ci->form_validation->run();


    }

    /**
     *
     * @internal
     */
    public function _populate_fields(){
        $post = $this->_ci->input->post();

        foreach ($this->fields as $field_name=>$field_obj) :

            // if its a file field pass
            if(in_array($this->_stable_name($field_obj->type), ['file', 'image'])):
                $field_obj->set_value($this->_upload_value($field_obj));
                continue;
            endif;

            if(!array_key_exists($field_name,$post)):
                continue;
            endif;
            $field_obj->set_value($post[$field_name]);
        endforeach;


    }

    /**
     * @internal
     * @param $field_obj
     * @return string
     */
    public function _upload_value($field_obj){

        // if field cannot be blank and is not set
        if(empty($_FILES[$field_obj->name]["name"])):

            if(FALSE == $field_obj->blank):

                $this->_ci->form_validation->set_rules(
                    $field_obj->get_widget_name(),
                    $field_obj->get_widget_name(),
                    ['required']);
            endif;


            return '';

        endif;

        // validate name
        if(strlen($_FILES[$field_obj->name]['name'])>0 &&
            !preg_match('/^[\w\-\_\.]+$/i', $_FILES[$field_obj->name]["name"])):

            $this->_ci->form_validation->set_rules(
                $field_obj->get_widget_name(),
                $field_obj->get_widget_name(),
                [
                    ['_do_upload', [$this, '_do_upload']]
                ]
            );

            $this->_ci->form_validation->set_message('_do_upload',
                'File name can only contain alpha-numeric characters, dashes, and underscores');

            return '';

        endif;

        // check if ends with a directory slash ToDo linux only since we are using forward slash
        if(!preg_match('/(.)*\/$/', $this->_ci->upload->upload_path)):
            $this->_ci->upload->upload_path = $this->_ci->upload->upload_path.DIRECTORY_SEPARATOR;
        endif;

        if(!preg_match('/(.)*\/$/', $field_obj->upload_to)):
            $field_obj->upload_to = $field_obj->upload_to.DIRECTORY_SEPARATOR;
        endif;

        // set the upload directory
        if(!empty($field_obj->upload_to)):
            $this->_ci->upload->upload_path = $this->_ci->upload->upload_path.$field_obj->upload_to;
        endif;

        // check if upload path exists
        if(!file_exists($this->_ci->upload->upload_path)){
            mkdir($this->_ci->upload->upload_path, 0777, true);
        }
        chmod($this->_ci->upload->upload_path, 0777);

        if (!$this->_ci->upload->do_upload($field_obj->get_widget_name()))
        {
            $this->_ci->form_validation->set_rules(
                $field_obj->get_widget_name(),
                $field_obj->get_widget_name(),
                [
                    ['_do_upload', [$this, '_do_upload']]
                ]
            );

            $this->_ci->form_validation->set_message('_do_upload', implode(',',$this->_ci->upload->error_msg));
        }


        return $field_obj->upload_to.$this->_ci->upload->data('file_name');
    }

    /**
     * @internal
     * @return bool
     */
    public function _do_upload(){
        return FALSE;
    }

    public function is_valid(){
        return $this->validate();
    }

    /**
     * @internal
     * @return mixed
     */
    public function _errors(){
        $validation_object =&_get_validation_object();
        return $validation_object->error_array();
    }

    public function errors(){

        if(!empty($this->_errors())):

            return '<p class="alert alert-danger">'.implode("\n", $this->_errors()). '</p>';

        endif;
    }

    /**
     * @ignore
     * @param $field_name
     * @return mixed
     */
    public function __get($field_name){

        if(array_key_exists($field_name, $this->fields)):
            return $this->get_field($field_name)->value;
        endif;
    }

    /**
     * @ignore
     * @param $field_name
     * @param $field_value
     */
    public function __set($field_name, $field_value){

        if(array_key_exists($field_name, $this->fields)):
            $this->get_field($field_name)->set_value($field_value);
        endif;
    }

    /**
     * @ignore
     * @param $field
     * @return bool
     */
    public function __isset($field){
        if(empty($this->fields)):
            return FALSE;
        endif;
        return array_key_exists($field, $this->fields);

    }

    /**
     * @ignore
     * @param $name
     * @return string
     */
    public function _stable_name($name){
        return strtolower($name);
    }

}

class ModelForm extends Form{

    /**
     * @internal
     * @var
     */
    public $model_object;

    /**
     * @internal
     * @var
     */
    public $fields;

    /**
     * @internal
     * @var
     */
    public $none_model_fields;

    /**
     * @internal
     * @var
     */
    protected $field_customize;

    /**
     * @internal
     * @var
     */
    private $only;

    /**
     * @internal
     * @var
     */
    private $ignored;

    /**
     * @internal
     * @var
     */
    private $extra_fields;

    /**
     * @ignore
     * @param PModel $context
     * @param array $initial
     */
    public function __construct(PModel $context, $initial=[]){
        parent::__construct($initial);
        $this->model_object = $context;
    }

    public function ignore($fields_names){
        if(!is_array($fields_names)):
            throw new OrmExceptions(
                sprintf('setting ignore() expects an array of arguments but got a %s', gettype($fields_names)));
        endif;

        $this->ignored = $fields_names;
    }

    public function only($fields_names){
        if(!is_array($fields_names)):
            throw new ValueError("only() expects an array of model fields to show");
        endif;
        $this->only = $fields_names;
    }

    public function customize($fields){
        if(empty($fields)):
            return FALSE;
        endif;
        // ensure we dont get a value passed i
        foreach ($fields as $key=>$opts) :
            if(array_key_exists('value', $opts)):
                throw new \InvalidArgumentException(sprintf("Field `%s` Trying to set value on { form->customize() },
                use { form->initial() } method", $key));
            endif;
        endforeach;

        $this->field_customize = $fields;
    }

    /**
     * Add to the form fields
     * @param $fields
     * @return bool
     */
    public function extra_fields($fields){
        if(empty($fields)):
            return FALSE;
        endif;

        $this->extra_fields = $fields;
    }

    public function form(){

        if(!empty($this->ignore) && !empty($this->only)):
            throw new OrmExceptions('setting only() and ignore() is not allowed on the same form');
        endif;
        $form_fields = [];

        // if only is set
        if(!empty($this->only)):
            foreach ($this->only as $field_name) :
                if(isset($this->model_object->meta->fields[$field_name])):
                    $form_fields[$field_name] = $this->model_object->meta->fields[$field_name]->form_field();
//                   $this->fields[$this->_stable_name($field_name)] = new FormField($opts);
                else:
                    throw new FormException(
                        sprintf('The field `%1$s` is not defined in the model `%2$s`, choices are : %3$s', $field_name,
                            $this->model_object->meta->model_name, stringify(array_keys($this->model_object->meta->fields))));
                endif;
            endforeach;
        endif;

        // if ignored set
        if(!empty($this->ignored)):
            $model_fields = $this->model_object->meta->fields;
            foreach ($model_fields as $field_name=>$field_obj) :

                if(in_array($field_name, $this->ignored)):
                    continue;
                endif;

                $form_fields[$field_name] = $model_fields[$field_name]->form_field();
//                $this->fields[$this->_stable_name($field_name)] = new FormField($opts);
            endforeach;
        endif;

        // if at this point fields is still empty just load all the fields in the model
        if(empty($form_fields)):

            foreach($this->model_object->meta->fields as $field_name=>$field_obj):
                $form_fields[$field_name] = $field_obj->form_field();

//                $this->fields[$this->_stable_name($field_name)] = new FormField($field_value);
            endforeach;

        endif;

        if(!empty($this->field_customize)):
            // ensure we are not customizing fields that are not in the form fields
            $form_fields_names = array_keys(array_change_key_case($form_fields, CASE_LOWER));
            foreach (array_keys($this->field_customize) as $field_name) :
                if(!in_array($this->_stable_name($field_name), $form_fields_names)):
                    throw new FormException(sprintf('Trying to customize { %s } that does not exist on the form', $field_name));
                endif;
            endforeach;
        endif;


        // create the fields now
        foreach ($form_fields as $field_name=>$field_opts) :
            $opts = $this->_merge_options($field_name,$field_opts);

            $this->fields[$this->_stable_name($field_name)] = new FormField($opts);
        endforeach;

        // then load extra fields
        if(!empty($this->extra_fields)):
            foreach ($this->extra_fields as $field_name=>$field_value) :
                $field_value['name'] = $field_name;
                
                // ensure required arguments are present
                $this->_validate_field($field_value);

                // if field with similar name is already load complain like hell
                if(in_array($this->_stable_name($field_value['name']), array_keys($this->fields))):
                    throw new DuplicateField(sprintf('The field `%s` seems to already exist on the form', $field_name));
                endif;

                // look for repeated fields
                if($field_value['type']=='repeat'):

                    if(!isset($field_value['repeat_field'])):
                        throw new FormException(
                            sprintf('The field %1$s is set as type { repeat } but no { repeat_field } has been provided',
                                $field_name));
                    endif;


                    if(array_key_exists($field_value['repeat_field'], $this->fields)):
                        $rep_name = $field_value['repeat_field'];
                        $repeated_field = $this->fields[$rep_name];
                        $repeated_field->validations = $repeated_field->validations + ['required'];
                        // unset it so that we can add them next to each other
                        unset($this->fields[$field_value['repeat_field']]);

                        $this->fields[$rep_name] = $repeated_field;
                        $field_value['validations']=["matches[$rep_name]"];
                        $field_value['type']= $repeated_field->type;


                        $opts = array_merge($this->_combine_opts($field_value, $repeated_field->get_skeleton()));

                        $this->fields[$this->_stable_name($field_name)] = new FormField($opts); ;
                    endif;

                    continue;
                endif;

                $this->fields[$this->_stable_name($field_name)] = new FormField($field_value);
            endforeach;

        endif;


        return parent::form();
    }

    /**
     * @ignore
     * @param $field_name
     * @param $from_model
     * @return mixed
     */
    public function _merge_options($field_name, $from_model){

        if(!empty($this->field_customize) && array_key_exists($field_name, $this->field_customize)):

            return $this->_combine_opts($this->field_customize[$field_name], $from_model);
        endif;

        return $from_model;
    }

    /**
     * @ignore
     * @param $new
     * @param $old
     * @return mixed
     */
    public function _combine_opts($new, $old){

        foreach ($new as $key=>$value) :


            if($key == 'validations' && array_key_exists($key, $old) && !empty($old[$key])):

                $old[$key] = array_merge($old[$key], $value);

                continue;
            endif;

            $old[$key] = $new[$key];
        endforeach;

        return $old;
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

    /**
     * @ignore
     * @return string
     */
    public function __toString(){
        return sprintf('< %s Form >', ucwords(strtolower($this->model_object->meta->model_name)));
    }
}