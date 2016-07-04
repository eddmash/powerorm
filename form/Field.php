<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 6/23/16
 * Time: 3:55 PM
 */

namespace powerorm\form\fields;


use powerorm\Contributor;
use powerorm\exceptions\ValueError;
use powerorm\form\widgets\EmailInput;
use powerorm\form\widgets\TextInput;
use powerorm\form\widgets\UrlInput;
use powerorm\Object;

/**
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
class Field extends Object implements Contributor
{
    public $form;
    public $name;
    public $widget;
    public $required=TRUE;

    public $label=NULL;
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

    public $help_text='';

    /**
     * Boolean that specifies whether the field is disabled, that is its widget is shown in the form but not editable.
     * @var bool
     */
    public $disabled=False;

    public $label_suffix=NULL;

    public $default_validators=[];

    public function __construct($opts=[]){

        $this->widget = (empty($this->widget)) ? new TextInput() :$this->widget ;

        $this->validators = [];

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

    }

    public function prepare_value($value){
        return $value;
    }

    /**
     * Given a Widget instance, returns an associative array of any HTML attributes
     * that should be added to the Widget, based on this Field.
     * @param $widget
     * @return array
     * @since 1.0.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function widget_attrs($widget){
        return [];
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
     * @param	string $label	The text to appear onscreen
     * @param	string	$id The id the label applies to
     * @param	array	$view_attrs Additional attributes
     * @return string
     */
    public function label($label=Null, $id=Null, $view_attrs=[]){

        // if the field is not hidden field set label
        if($this->widget->input_type=='hidden') :
            return '';
        endif;

        $label_id = $this->name;


        $view_attrs = array_merge($view_attrs, $this->label_attrs);

        if(!empty($view_attrs)):
            if(isset($view_attrs['name'])):
                $this->label_name = $view_attrs['name'];
                unset($view_attrs['name']);
            endif;
            if(isset($view_attrs['id'])):
                $label_id = $view_attrs['id'];
                unset($view_attrs['id']);
            endif;
        endif;

        return form_label($this->get_label_name(), $label_id, $view_attrs);
    }

    /**
     * Returns the label name .
     * @return mixed|string
     */
    public function get_label_name(){
        // incase form label is not set
        if(empty($this->label_name)):
            return str_replace('_',' ', ucwords(strtolower($this->name)));
        endif;

        return $this->label_name;
    }

    public function bound_value($data, $initial)
    {
        return $data;
    }

    public function contribute_to_class($name, $object){
        $this->set_from_name($name);
        $object->load_field($this);
        $this->form = $object;
    }

    public function set_from_name($name){
        $this->name = $name;
        $this->label = $name;
    }

    public function as_widget($widget=NULL, $attrs=NULL, $only_initial=NULL)
    {
        if($widget==NULL):
            $widget = $this->widget;
        endif;

        return (string)$widget->render($this->name, $this->value(), $attrs);
    }

    public function value()
    {
        $name = $this->name;

        $value = $this->initial;

        if(!$this->form->is_bound):
            if(array_key_exists($this->name, $this->form->initial)):
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

    public function is_hidden(){
        return $this->widget->is_hidden();
    }

    public function is_editable(){
        return $this->editable;
    }

    public function __toString()
    {
        return $this->as_widget();
    }
}

class CharField extends Field{

    public $max_length;
    public $min_length;

    public function __construct($opts=[]){
        parent::__construct($opts);

        if($this->max_length):
            $this->validators[] = sprintf('max_length[%s]', $this->max_length);
        endif;

        if($this->min_length):
            $this->validators[] = sprintf('min_length[%s]', $this->min_length);
        endif;
    }
}

class EmailField extends CharField{

    public $default_validators = ['valid_email'];

    public function __construct($opts=[]){
        $this->widget = new EmailInput;

        parent::__construct($opts);
    }
}

class UrlField extends CharField{

    public $default_validators = ['valid_url'];

    public function __construct($opts=[]){
        $this->widget = new UrlInput;

        parent::__construct($opts);
    }
}