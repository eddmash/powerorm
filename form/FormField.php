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
    public $blank=TRUE;
    public $value;
    public $label_name;
    public $label_attrs=[];
    public $choices;
    public $validations;
    protected $_errors = [];
    protected $form_display_field;
    public $form_value_field = '';
    public $empty_label;
    public $help_text;
    public $attrs;
    public $upload_to;

    public function __construct($field_options=[]){

        foreach ($field_options as $key=>$opt) :
            $this->{$key} = $opt;
        endforeach;
    }

    public function get_skeleton(){
        $parts = get_object_vars($this);
        unset($parts['_errors']);
        return $parts;
    }

    public function errors(){
        $this->_field_errors();
        if(!empty($this->_errors)):

            return '<p class="alert alert-danger">'.implode("\n", $this->_errors). '</p>';
        endif;
    }

    public function label($label=Null, $id=Null, $view_attrs=[]){

        // if the field is not hidden field set label
        if($this->type=='hidden') :
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
                $widget = $this->_choice_widget(TRUE);
                break;
            case "dropdown":
                $widget = $this->_dropdown_widget($field_attr);
                break;
            case "multiselect":
                $widget = $this->_dropdown_widget($field_attr, TRUE);
                break;
            case "textarea":
                $widget = form_textarea($field_attr);
                break;
            case "currency":
                $this->type = 'select';
                $field_attr['type'] = 'select';
                $this->choices = currency_list();
                $widget = $this->_dropdown_widget($field_attr);
                break;
            case "country":
                $this->type = 'select';
                $field_attr['type'] = 'select';
                $this->choices = countries_list();
                $widget = $this->_dropdown_widget($field_attr);
                break;
            case "file":
            case "image":
                $widget = '';

                if($this->value && $this->type =='image'){
                    $widget .= '<img class="uploaded-image" src="'.uploaded($this->value).'">';
                }

                if($this->value && $this->type =='file'){
                    $widget .= "<a href='".uploaded($this->value)."'>file</a>";
                }
                $widget .= form_upload($field_attr);
                break;
            case 'uploadloader':
                if($this->value){
                    $widget .= '<img class="uploaded-image" src="'.uploaded($this->value).'">';
                }
                $field_attr['readonly'] = 'readonly';
                $widget .= "<div class='fm_upload_button' id='".$this->name."'>".form_input($field_attr);
                $widget .= "<span class='message'> Add File</span> </div>";
                break;
            default:
                $widget = form_input($field_attr);
                break;

        endswitch;
        return $widget;
    }

    public function validation_rules(){

        // since the validator works with post data,
        // we pass uploads because the data is stored in $_Files
        if($this->type == 'file' || $this->type == 'image'):
            return [];
        endif;

        $rules = [];

        if(!empty($this->max_length)):
            $rules[] = 'max_length['.$this->max_length.']';
        endif;

        if(!in_array(strtolower($this->name), ['file', 'image']) && $this->blank==FALSE):
            $rules[] = 'required';
        endif;

        if($this->type=='email'):
            $rules[] = 'valid_email';
        endif;

        if(!empty($this->validations)):
            foreach ($this->validations as $rule) :

                if(in_array($rule, $rules)):
                    $pos = array_search($rule, $rules);
                    array_splice($rules, $pos, 1);
                endif;

                $rules[] = $rule;
            endforeach;
        endif;

        return $this->_ensure_rule_exists($rules);
    }

    public function _choice_widget($radio=FALSE){
        $widget = '';
        $class = 'checkbox-widget';
        if($radio):
            $class = 'radio-widget';
            $callback = 'form_radio';
        else:
            $callback = 'form_checkbox';
        endif;

        $value = $this->_choices_value($this->value);

        $choices = $this->_prepare_choices($this->choices);

        if(!empty($this->empty_label)):
            $choices = array_merge([" "=>$this->empty_label], $choices);
        endif;

        if(!empty($choices)):
            foreach ($choices as $sys_name=>$human_readable) :
                $checked = $this->_checkbox_value($value, $sys_name);

                $widget .= sprintf('<span class="%3$s"> %1$s &nbsp; %2$s &nbsp; </span>',
                    $callback($this->get_widget_name(), $sys_name, $checked), $human_readable, $class);
            endforeach;
        else:
            // just create one checkbox
            $widget .= sprintf('<span class="%3$s"> %1$s &nbsp; %2$s &nbsp; </span>',
                $callback($this->get_widget_name(), $this->get_widget_name()), $this->get_label_name(), $class);
        endif;

        return $widget;

    }

    public function _dropdown_widget($attrs=[], $multiselect=FALSE){
        $widget = '';

        $value = $this->_choices_value($this->value);

        $checked = [];
        $choices = [];

        if(!empty($attrs)):

            if(array_key_exists('name', $attrs)):
                unset($attrs['name']);
            endif;
            if(array_key_exists('value', $attrs)):
                unset($attrs['value']);
            endif;
            if(array_key_exists('maxlength', $attrs)):
                unset($attrs['maxlength']);
            endif;
            if(array_key_exists('placeholder', $attrs)):
                unset($attrs['placeholder']);
            endif;
        endif;

        if(!empty($this->choices)):
            $choices = $this->_prepare_choices($this->choices);
            foreach ($choices as $sys_name=>$human_readable) :

                $opt = $this->_selected_value($value, $sys_name);

                if(!empty($opt)):
                    $checked[] = $opt;
                endif;
            endforeach;
        endif;

        if(!empty($this->empty_label)):
            $choices = [" "=>$this->empty_label] + $choices;
        endif;

        if($multiselect):
            $widget .=  form_multiselect($this->get_widget_name(), $choices, $checked, $attrs) ;
        else:
            $widget .=  form_dropdown($this->get_widget_name(), $choices, $checked, $attrs) ;
        endif;

        return $widget;
    }

    public function help_text(){
        return sprintf("<small class='help-block'> %s </small>", ucfirst($this->help_text));

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

        if(!empty($this->attrs)):

            $attrs = array_merge($attrs, $this->attrs);
        endif;

        if($this->blank):
            $attrs['required']  =  $this->blank;
        endif;

        $rigid_attrs = ['name'];

        foreach ($view_attrs as $attr_name=>$attr_value) :
            if(in_array($attr_name, $rigid_attrs)):
                continue;
            endif;

            if(strtolower($attr_name) == 'help_text'):
                $this->help_text = $attr_value;
                continue;
            endif;

            if(strtolower($attr_name) == 'class' && isset($attrs[$attr_name])):
                $attrs[$attr_name] = $attrs[$attr_name].' '.$attr_value;
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
            $value = $value->{$pk};
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

    public function get_widget_name(){
        $name = $this->name;
        if(in_array($this->type, ['multiselect', 'checkbox'])):
            $name = $name."[]";
        endif;
        return $name;
    }

    public function get_label_name(){
        // incase form label is not set
        if(empty($this->label_name)):
            return str_replace('_',' ', ucwords(strtolower($this->name)));
        endif;

        return $this->label_name;
    }


    /**
     * Ensures rules set in the fields actually exist
     * @ignore
     * @param $rules
     * @return mixed
     */
    public function _ensure_rule_exists($rules){

        $new_rules = [];
        foreach ($rules as $validation_rule):
            // if its an array already just pass and use what was passed
            // in `array('is_valid_upload', $this, 'is_valid_upload')`
            if(is_array($validation_rule)):
                $new_rules = array_merge($new_rules, $validation_rule);
            endif;

            if(is_string($validation_rule)):

                $validation_name = $validation_rule;

                //remove the brackets to get the validation name
                if(preg_match('/[\[(.*?)\]]/', $validation_rule)):
                    // split at the between the first two brackets
                    $validation_name = preg_split('/(\[(.*?)\])/', $validation_rule)[0];
                endif;

                // if method does not exist in the ci form validation library
                if(!method_exists(get_instance()->form_validation, $validation_name)):
                    $field_validations = new Validations();

                    // try our custom library
                    if(method_exists($field_validations, $validation_name)):
                        // remove it from the array
                        array_splice($rules, array_search($validation_name, $rules), 1);

                        // because of CI FORM VALIDATION works we
                        // set it as an array within an array because we need to be able to set the error message
                        $new_rule = array($validation_name, array($field_validations, $validation_rule));

                        // try getting from the custom validations
                        array_push($new_rules, $new_rule);

                    endif;
                else:
                    $new_rules[] = $validation_rule;
                endif;

            endif;

        endforeach;

        return $new_rules;
    }


}

