<?php
/**
 * Class that creates forms based on fields passed in.
 */
/**
 *
 */
namespace powerorm\form;
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
     *
     * Assuming out model has two field name and age;
     *
     * <pre><code> $initial =[
     *  'name'=>'mat',
     *  'age'=>10
     * ];
     *
     * new Form($initial);
     * </code></pre>
     *
     *
     * @param array $initial the data displayed on form fields when the form is first loaded.
     */
    public function __construct($initial=[]){
        $this->_ci =& get_instance();
        $this->initial = $initial;
    }

    /**
     * Gets a single field instance in the form fields array and returns it
     *
     * <h4>Usage</h4>
     *
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
     * Creates an opening form tag with a base URL built from your config preferences.
     *
     * It will optionally let you add form attributes and hidden input fields, and will always add the accept-charset
     * attribute based on the charset value in your config file.
     *
     *<pre><code>assuming we are using the controller `user/signup` to server this form.
     * echo $form->open(); // goes back to the controller method that served this form.
     * <form method="post" accept-charset="utf-8" action="http://example.com/index.php/user/signup">
     *
     * echo form->open('email/send'); // goes to the base_url  plus the “email/send” URI segments
     * <form method="post" accept-charset="utf-8" action="http://example.com/index.php/email/send">
     * </code></pre>
     *
     * This method also detects if the form contains any upload fields a generate a multipart form if they are found.
     * it is also responsible for displaying the form help_text.
     *
     * <h4>Adding Attributes</h4>
     *
     * Attributes can be added by passing an associative array to the second parameter, like this:
     *
     * <pre><code>$attributes = array('class' => 'email', 'id' => 'myform');
     * echo form->open('email/send', $attributes);</code></pre>
     *
     * Alternatively, you can specify the second parameter as a string:
     *
     * <pre><code>echo form->open('email/send', 'class="email" id="myform"');</code></pre>
     *
     * The above examples would create a form similar to this:
     *
     * <pre><code>&lt; form method="post" accept-charset="utf-8"
     * action="http://example.com/index.php/email/send" class="email" id="myform" &gt;</code></pre>
     *
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
     * Create the form closing tags and displays any errors that have not been display explicitly.
     *
     * <pre><code>echo form_close($string);</code></pre>
     *
     *
     * Would produce:
     * <pre><code> &lt;/form &gt;</pre>
     *
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
     * @ignore
     * @param $opts
     * @throws ValueError
     */
    public function _validate_field($opts){

        if(!isset($opts['type'])):
            throw new ValueError(sprintf("Please provide the { type } for the field `%s`", $opts['name']));
        endif;
    }

    /**
     * Returns errors related to the form as a whole.
     * @return string
     */
    public function errors(){

        if(!empty($this->_errors())):

            return '<p class="alert alert-danger">'.implode("<br>", $this->_errors()). '</p>';

        endif;
    }

    /**
     * This method does all the heavy lifting and returns the form object to use.
     *
     * - Prepares the form fields for use.
     * - Loads the initial data onto the form fields.
     * - It load the necessary libraries needed by this form.
     *
     * <pre><code>$form = $form_builder->form();</code></pre>
     *
     * @return $this
     */
    public function form(){
        // ensure we a consitent naming scheme for the fields.
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

    /**
     * This method is responsible for setting the initial data to be used on the form.
     *
     * NB this method only set, form() method is resposible for loading this initial data.
     *
     * This method beyond the normal php data types also accepts a
     * - Queryset- this will be evaluated to get the value.
     * - Any object, as long as its a model object and the model extends the PModel, as long as its primary key is set.
     * as a value for the field
     *
     * <pre><code>
     *  $user = $this->user_model->get(['username'=>'ken']);
     *  $form_builder->initial(['username'=>$user', 'password'=>'#$RAdad']);
     * </code></pre>
     * @param array $data
     */
    public function initial($data=[]){
        if(!is_array($data)):
            throw new \InvalidArgumentException('The initial data should be an associative array,
            of field name and value');
        endif;

        foreach ($data as $key=>$value) :
            if(array_key_exists($key, $this->initial)):
                $this->initial[$key] = $value;
            endif;
        endforeach;
    }

    /**
     * This is the help information that relates to the whole form and not a specific to a field on the form.
     * {@link http://eddmash.github.io/powerorm/docs/classes/powerorm.form.Field.html#method_help_text}
     * <pre><code>$form_builder->help_text(['Please ensure the email and phone number are valid we
     * will use them for activation of you're account']);
     * </code></pre>
     *
     * @param string $message the help information to be displayed with form
     */
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

    /**
     * This method runs the validation of the form. and return true on success or false on fail.
     * @return mixed
     */
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