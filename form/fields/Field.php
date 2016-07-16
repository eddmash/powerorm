<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 6/23/16
 * Time: 3:55 PM
 */

namespace powerorm\form\fields;


use powerorm\Contributor;
use powerorm\exceptions\ValidationError;
use powerorm\exceptions\ValueError;
use powerorm\form\widgets\TextInput;
use powerorm\Object;

/**
 * Base class for all form fields, should nevers be initialized, use its subclasses.
 *
 * required -- Boolean that specifies whether the field is required.
 *             True by default.
 * widget -- A Widget class, or instance of a Widget class, that should
 *           be used for this Field when displaying it. Each Field has a
 *           default Widget that it'll use if you don't specify this. In
 *           most cases, the default widget is TextInput.
 * label -- A verbose name for this field, for use in displaying this
 *          field in a form. By default, Django will use a "pretty"
 *          version of the form field name, if the Field is part of a
 *          Form.
 * initial -- A value to use in this Field's initial display. This value
 *            is *not* used as a fallback if data isn't given.
 * help_text -- An optional string to use as "help text" for this Field.
 * error_messages -- An optional dictionary to override the default
 *                   messages that the field will raise.
 * show_hidden_initial -- Boolean that specifies if it is needed to render a
 *                        hidden widget with initial value after widget.
 * validators -- List of additional validators to use
 * localize -- Boolean that specifies if the field should be localized.
 * disabled -- Boolean that specifies whether the field is disabled, that
 *             is its widget is shown in the form but not editable.
 * label_suffix -- Suffix to be added to the label. Overrides
 *
 * @package powerorm\form\fields
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Field extends Object implements Contributor
{
    public $form;
    public $name;
    public $widget;
    public $required=TRUE;

    protected $label=NULL;

    /**
     * The initial value to used when displaying a form that is not bound with data, i.e.
     * before user types in and submits the form.
     *
     * You may be thinking, why not just pass a dictionary of the initial values as data when displaying the form? Well,
     * if you do that, you’ll trigger validation, and the HTML output will include any validation errors
     *
     * Note initial values are not used as “fallback” data in validation if a particular field’s value is not given.
     * initial values are only intended for initial form display:
     * @var null
     */
    public $initial=NULL;

    /**
     * Any help text that has been associated with the field.
     * @var string
     */
    public $help_text='';

    /**
     * Boolean that specifies whether the field is disabled, that is its widget is shown in the form but not editable.
     * @var bool
     */
    public $disabled=False;

    public $label_suffix=NULL;

    public $default_validators=[];

    /**
     * A list of some custom validators to run, this provides an easier way of implementing custom validations
     * to your field.
     *
     * @var array
     */
    public $custom_validators = [];

    public function __construct($opts=[]){

        $this->widget = (empty($this->widget)) ? $this->get_widget() :$this->widget ;

        $this->validators = [];
        $this->my_validators = [];

        // replace the default options with the ones passed in.
        foreach ($opts as $key=>$value) :
            $this->{$key} = $value;
        endforeach;

        $this->initial = ($this->initial==NULL)? [] : $this->initial;

        $this->widget->is_required = $this->required;

        // Hook into this->widget_attrs() for any Field-specific HTML attributes.
        $extra_attrs = $this->widget_attrs($this->widget);

        if($extra_attrs):
            $this->widget->attrs = array_merge($this->widget->attrs, $extra_attrs);
        endif;

        if(!is_array($this->validators)):
            throw new ValueError(' { validators } is expected to be an array of validation to apply on the field');
        endif;

        if($this->required):
            $this->validators[] = 'required';
        endif;

        $this->validators = array_merge($this->default_validators, $this->validators);

        $this->custom_validators = array_merge($this->custom_validators, $this->my_validators);

    }

    public function prepare_value($value){
        return $value;
    }

    /**
     * Given a Widget instance, returns an associative array of any HTML attributes
     * that should be added to the Widget, based on this Field. this is a good place to ensure that the attributes field
     * matches to there related html attributes e.g for form field we get mx_length but html expexts maxlength.
     *
     * @param $widget
     * @return array
     * @since 1.0.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function widget_attrs($widget){
        return [];
    }

    /**
     * Returns the Widget to use for this form field.
     * @return static
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_widget(){
        return TextInput::instance();
    }

    public function to_php($value){
        return $value;
    }
 
    public function validators(){
        return $this->validators;
    }

    /**
     * Returns fields label.
     *
     * Lets you generate a <label>. Simple example:
     *
     * <pre><code>echo field->label('What is your Name', 'username');</code></pre>
     * // Would produce:  <label for="username">What is your Name</label>
     *
     * Similar to other functions, you can submit an associative array in the third parameter if you prefer to set additional attributes.
     *
     * Example:
     * <pre><code> $attributes = array(
     *  'class' => 'mycustomclass',
     *  'style' => 'color: #000;'
     * );
     *
     * echo field->label('What is your Name', 'username', $attributes);</code></pre>
     *
     * // Would produce:  <label for="username" class="mycustomclass" style="color: #000;">What is your Name</label>
     *
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function label_tag(){

        // if the field is not hidden field set label
        if($this->widget->is_hidden()) :
            return '';
        endif;

        return form_label($this->get_label_name(), $this->get_id_for_label(), []);
    }

    /**
     * Returns the label name .
     * @return mixed|string
     */
    public function get_label_name(){
        // incase form label is not set
        if(empty($this->label)):
            return str_replace('_',' ', ucwords(strtolower($this->name)));
        endif;

        return ucfirst($this->label);
    }
    
    public function get_auto_id(){

        if(is_string($this->form->auto_id) && strpos($this->form->auto_id, '%s')):
            return sprintf($this->form->auto_id, $this->name);
        endif;

        if(is_bool($this->form->auto_id) && $this->form->auto_id):
            return $this->name;
        endif;

        return '';
    }

    /**
     * The html label ID that will be used for this field.
     *
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_id_for_label(){
        $id = (array_key_exists('id', $this->widget->attrs)) ? $this->widget->attrs['id'] : $this->get_auto_id();
        return $this->widget->get_id_for_label($id);
    }

    public function bound_value($data, $initial)
    {
        return $data;
    }

    public function contribute_to_class($name, $object){
        $this->set_from_name($name);
        $object->load_field($this);

        $object->field_validation_rules([
            'field'=>$this->get_html_name(),
            'label'=>$this->get_label_name(),
            'rules'=>$this->validators()
        ]);
        $this->form = $object;
    }

    /**
     * The name of the field that will be used in the input element’s name field i.e
     * Returns the name to use in widgets, this is meant to help prepare names for fields like checkbox that take
     * the name as an array
     *
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_html_name(){
        return $this->name;
    }

    public function clean($value){
        $value = $this->to_php($value);
        $this->validate($value);
        $this->run_validators($value);
        return $value;
    }

    /**
     * Some validations that the CI_Validator does not take care off
     * This method should raise a ValiationError Exception if the field fails validation
     * @param $value
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function validate($value){

    }

    /**
     * Runs custom validation not provided by CI_Validator
     *
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function run_validators($value){

        // collect all validation errors for this field
        $errors = [];
        foreach ($this->custom_validators as $validator) :
            try{
                call_user_func_array($validator, $value);

            }catch (ValidationError $e){
                $errors[] = $e;
            }
        endforeach;

        if(!empty($errors)):
            throw new ValidationError($errors);
        endif;

    }

    public function set_from_name($name){
        $this->name = $name;
        $this->label = $this->get_label_name();
    }

    public function as_widget($widget=NULL, $attrs=[], $only_initial=NULL)
    {
        if($widget==NULL):
            $widget = $this->widget;
        endif;
        
        if($this->disabled):
            $attrs['disabled'] = TRUE;
        endif;
        
        if(!empty($this->get_auto_id()) &&
            !array_key_exists('id', $attrs) &&
            !array_key_exists('id', $this->widget->attrs)):
            $attrs['id'] = $this->get_auto_id();
        endif;

        return (string)$widget->render($this->get_html_name(), $this->value(), $attrs);
    }

    /**
     * The value of the field.
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function value()
    {
        $name = $this->name;

        $value = $this->initial;

        if(!$this->form->is_bound):
            if(array_key_exists($name, $this->form->initial)):
                $value = $this->form->initial[$name];
            endif;
        else:
            $initial = (array_key_exists($name, $this->form->initial)) ? $this->form->initial[$name]: $this->initial;

            $value = $this->bound_value($this->data(), $initial);
        endif;

        return $this->prepare_value($value);
    }

    public function data()
    {
        return $this->widget->value_from_data_collection($this->form->data, $this->name);
    }

    /**
     *  attribute is True if the form field is a hidden field and False otherwise
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function is_hidden(){
        return $this->widget->is_hidden();
    }

    public function __toString()
    {
        return $this->as_widget();
    }
}







