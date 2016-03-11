<?php
namespace powerorm\form;

use powerorm\exceptions\FormException;
use powerorm\queries\Queryset;

/**
 * Class FormField
 * @package powerorm\form
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FormField{
    public $type;
    public $name;
    public $unique;
    public $max_length;
    public $default;
    public $blank;
    public $value;
    public $label_name;
    public $choices;
    protected $_errors = [];
    protected $form_display_field;
    public $form_value_field = '';
    public $empty_label;

    public function __construct($field_options=[]){
//        var_dump($field_options);
        foreach ($field_options as $key=>$opt) :
            $this->{$key} = $opt;
        endforeach;

        // incase form label is not set
        if(empty($this->label_name)):
            $this->label_name = str_replace('_',' ', ucwords(strtolower($this->name)));
        endif;

    }

    public function errors(){
        $this->_field_errors();

        return implode('\n', $this->_errors);
    }

    public function label($view_attrs = []){

        if(!is_array($view_attrs)):
            throw new FormException("Form label() expects and array as argument");
        endif;

        // if the field is not hidden field set label
        if($this->type=='hidden') :
            return '';
        endif;
        $label_id = $this->name;

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

        return form_label($this->label_name, $label_id, $view_attrs);
    }

    public function widget($view_attrs=[]){

        $field_attr = $this->_attrs($view_attrs);
        $widget = NULL;
        switch($this->type):
            case "hidden":
                $widget = form_hidden($this->get_widget_name(), $this->value);
                break;
            case "password":
                $widget = form_password($field_attr);
                break;
            case "checkbox":
                $widget = $this->_choice_widget();
                break;
            case "radio":
                $widget = $this->_radio_widget();
                break;
            case "dropdown":
                $widget = $this->_dropdown_widget();
                break;
            case "multiselect":
                $widget = $this->_dropdown_widget(TRUE);
                break;
            case "textarea":
                $widget = form_textarea($field_attr);
                break;
            case "currency":
                $this->type = 'select';
                $field_attr['type'] = 'select';
                $this->choices = currency_list();
                $widget = $this->_dropdown_widget();
                break;
            case "country":
                $this->type = 'select';
                $field_attr['type'] = 'select';
                $this->choices = countries_list();
                $widget = $this->_dropdown_widget();
                break;
            default:
                $widget = form_input($field_attr);
                break;

        endswitch;

        return $widget;
    }

    public function validation_rules(){

        $rules = [];

        if(!empty($this->max_length)):
            $rules[] = 'max_length['.$this->max_length.']';
        endif;

        if(empty($this->blank)):
            $rules[] = 'required';
        endif;

        if($this->type=='email'):
            $rules[] = 'valid_email';
        endif;

        return $rules;
    }

    public function _choice_widget(){
        $widget = '';

        $value = $this->_choices_value($this->value);

        $choices = $this->_prepare_choices($this->choices);
        if(!empty($this->empty_label)):
            $choices = array_merge([" "=>$this->empty_label], $choices);
        endif;

        foreach ($choices as $sys_name=>$human_readable) :
            $checked = $this->_checkbox_value($value, $sys_name);

            $widget .= sprintf('<span class="checkbox-widget"> %1$s &nbsp; %2$s &nbsp; </span>',
                        form_checkbox($this->get_widget_name(), $sys_name, $checked), $human_readable);
        endforeach;

        return $widget;

    }

    public function _radio_widget(){
        $widget = '';

        $value = $this->_choices_value($this->value);

        foreach ($this->choices as $sys_name=>$human_readable) :
            $checked = $this->_checkbox_value($value, $sys_name);

            $widget .= sprintf('<span class="radio-widget"> %1$s &nbsp; %2$s &nbsp; </span>',
                form_radio($this->get_widget_name(), $sys_name, $checked), $human_readable);
        endforeach;

        return $widget;

    }

    public function _dropdown_widget($multiselect=FALSE){
        $widget = '';
        $value = $this->_choices_value($this->value);

        $checked = [];
        foreach ($this->choices as $sys_name=>$human_readable) :
            $opt = $this->_selected_value($value, $sys_name);
            if(!empty($opt)):
                $checked[] = $opt;
            endif;
        endforeach;

        $choices = $this->_prepare_choices($this->choices);

        if(!empty($this->empty_label)):
            $choices = [" "=>$this->empty_label] + $choices;
        endif;
        if($multiselect):
            $widget .=  form_multiselect($this->get_widget_name(), $choices, $checked) ;
        else:
            $widget .=  form_dropdown($this->get_widget_name(), $choices, $checked) ;
        endif;

        return $widget;
    }

    public function _attrs($view_attrs){
        if(!is_array($view_attrs)):
            throw new FormException("Form widget() expects and array as argument");
        endif;

        $attrs = array(
            'name' => $this->get_widget_name(),
            'placeholder' => ucwords(str_replace('_', ' ', $this->name.' ...')),
            'id'  =>  $this->name,
            'maxlength'  =>  $this->max_length,
            'value' => (!is_array($this->value))? $this->value: '',
        );

        if($this->blank):
            $attrs['required']  =  $this->blank;
        endif;

        $rigind_attrs = ['name'];

        foreach ($view_attrs as $attr_name=>$attr_value) :
            if(in_array($attr_name, $rigind_attrs)):
                continue;
            endif;

            $attrs[$attr_name] = $attr_value;
        endforeach;

        return $attrs;
    }

    public function _choices_value($values){
        $value = $values;

        if($value instanceof Queryset):
            $value = $value->value();
        endif;

        if(is_object($value)):
            $pk = $value->meta->primary_key->name;
            $value = $this->value->{$pk};
        endif;

        if(is_array($value)):
            $vals = [];

            foreach ($value as $val) :
                $vals[] = $this->_choices_value($val);
            endforeach;
            $value = $vals;
        endif;

        return $value;
    }

    public function _prepare_choices($choices){

        $form_choices = $choices;

        if($choices instanceof Queryset):
            $choices = $choices->value();

            if(is_array($choices)):
                $form_choices = [];
                foreach ($choices as $choice) :
                    $form_choices = $form_choices + $this->_prepare_choices($choice);
                endforeach;

            endif;

        endif;

        if(is_object($choices) && $choices instanceof \PModel):

            $form_choices = [];

            // value for the choices
            $value_field = $this->form_value_field;
            if(empty($value_field)):
                $value_field = $choices->meta->primary_key->name;
            else:
                $value_field = $choices->meta->get_field($value_field)->name;
            endif;

            // choice name to display
            $label_name = $this->form_display_field;
            if(empty($label_name)):
                $label_name = $choices;
            else:
                $label_name = $choices->{$label_name};
            endif;

            $form_choices[$choices->{$value_field}] = $label_name;
        endif;

        return $form_choices;
    }

    public function _checkbox_value($values, $current){

        if(is_array($values) && in_array($current, $values)):
            return TRUE;
        endif;

        if($values == $current):
            return TRUE;
        endif;
    }

    public function _selected_value($values, $current){

        if(is_array($values) && in_array($current, $values)):
            return $current;
        endif;

        if($values == $current):
            return $current;
        endif;
    }

    public function _field_errors(){
        if(empty($this->_errors())):
            return '';
        endif;

        if(array_key_exists($this->name, $this->_errors())):
            $this->_errors[] = $this->_errors()[$this->name];
        endif;
    }

    public function _errors(){
        $validation_object =&_get_validation_object();
        return $validation_object->error_array();
    }

    public function set_value($value){
        $this->value= $value;
    }

    public function __set($field_name, $field_value){
        $this->value= $field_value;
    }

    public function get_widget_name(){
        $name = $this->name;
        if(in_array($this->type, ['multiselect', 'checkbox'])):
            $name = $name."[]";
        endif;
        return $name;
    }

}

