<?php
namespace powerorm\model\field;

use powerorm\exceptions\OrmExceptions;
use powerorm\form;


/**
 * This class represents a column in the database table.
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Field{
    public $name=Null;
    public $type;
    public $null=FALSE;
    public $unique=FALSE;
    public $max_length=NULL;
    public $primary_key=FALSE;
    public $auto=FALSE;
    public $default=' ';
    public $signed=FALSE;
    public $constraint_name;
    public $db_column=Null;
    public $db_index=Null;
    public $form_blank=FALSE;
    public $choices;
    public $form_widget;
    public $empty_label;
    public $container_model;

    public function __construct($field_options = []){

        // if some passes type remove it,
        // we don't people breaking our perfect flow of things.
        if(isset($field_options['type'])):
            unset($field_options['type']);
        endif;

        // replace the default options with the ones passed in.
        foreach ($field_options as $key=>$value) :
            $this->{$key} = $value;
        endforeach;

        // null status
        if($this->primary_key):
            $this->null = FALSE;
        endif;

        if(!in_array('form_blank', $field_options)):
            $this->form_blank = $this->null;
        endif;

        $this->type = $this->db_type();

    }

    /**
     * Calculates the actual column name in the database, especially useful for foreign keys
     * @return null
     */
    public function db_column_name(){
        return $this->name;
    }

    public function __validate_name(){

    }

    public function skeleton(){
        return [
            'field_options'=>$this->options(),
            'class'=>get_class($this)
        ];
    }

    public function options(){
        $prefix = '';
        if($this->unique):
            $prefix = 'uni';
        endif;

        if($this->db_index):
            $prefix = 'idx';
        endif;

        if(empty($this->constraint_name)):
            $this->constraint_name = $this->constraint_name($prefix);
        endif;

        $opts = get_object_vars($this);

        unset($opts['form_blank']);
        unset($opts['choices']);
        unset($opts['form_widget']);
        unset($opts['empty_label']);
        unset($opts['form_display_field']);
        unset($opts['form_value_field']);
        unset($opts['choices']);

        return $opts;
    }

    public function constraint_name($prefix){
        if(empty($prefix)):
            return '';
        endif;

        return sprintf('%1$s_%2$s_%3$s', $prefix, strtolower($this->name), mt_rand());
    }

    public function form_field(){

        return $this->_prepare_form_field();
    }

    public function _prepare_form_field(){

        $opts = [];
        $opts['type'] = (!empty($this->form_widget))? $this->form_widget: $this->form_type();
        $opts['name'] = $this->name;
        $opts['unique'] = $this->unique;
        $opts['max_length'] = $this->max_length;
        $opts['default'] = $this->default;
        $opts['blank'] = $this->form_blank;
        $opts['empty_label'] = $this->empty_label;
        $opts['choices'] = $this->choices;

        return $opts;
    }

    public function db_type(){
        return '';
    }

    public function form_type(){

        return 'text';
    }

    public function __set($key, $value){
        if($key==='value'):
            $this->{'value'} = $value;
        endif;
    }

    public function __toString(){
        return ''.$this->name;
    }

    public function clean($context, $value){

    }

    /**
     * Should return a list of checks Message instances.
     * @return array
     */
    public function check(){
        return [];
    }

    public function add_check($checks, $new_check){
        if(!empty($new_check)):
            $checks = array_merge($checks, $new_check);
        endif;
        return $checks;
    }

    public function validate(){

    }


}

/**
 * Class CharField
 */
class CharField extends Field{

    public function __construct($options=[]){

        parent::__construct($options);

        if(!empty($this->max_length)):
            $this->type = strtoupper(sprintf('%1$s(%2$s)', $this->type, $this->max_length));
        endif;
    }

    public function db_type(){
        return 'VARCHAR';
    }

    public function form_type(){

        $type = 'text';

        if(!empty($this->choices)):
            $type = 'dropdown';
        endif;

        return $type;
    }

    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_max_length_check());
        return $checks;
    }

    public function _max_length_check(){
        if(empty($this->max_length)):
            return [\powerorm\checks\check_error($this, sprintf('%s requires `max_length` to be set', get_class($this)))];
        endif;

        if(!empty($this->max_length) && $this->max_length < 0):
            return [\powerorm\checks\check_error($this, sprintf('%s requires `max_length` to be a positive integer', get_class($this)))];
        endif;
    }

}

class EmailField extends CharField{

    public function __construct($options=[]){
        //  254 in order to be compliant with RFC3696/5321
        $options['max_length'] = 254;
        parent::__construct($options);
    }

    public function form_type(){
        return 'email';
    }
}

class FileField extends CharField{
    protected $passed_pk;
    protected $passed_unique;
    public $upload_to;

    public function __construct($options=[]){
        $options['max_length'] = 100;
        $this->passed_pk = (array_key_exists('primary_key', $options))? TRUE: FALSE;
        $this->passed_unique = (array_key_exists('unique', $options))? TRUE: FALSE;
        parent::__construct($options);
    }

    public function form_type(){
        return 'file';
    }

    public function check(){
        $checks = parent::check();

        $checks = $this->add_check($checks, $this->_check_primarykey());

        $checks = $this->add_check($checks, $this->_check_unique());
        return $checks;
    }

    public function _check_unique(){
        if($this->passed_unique):
            return [\powerorm\checks\check_error($this, sprintf("'unique' is not a valid argument for a %s.", get_class($this)))];
        endif;
        return [];
    }

    public function _check_primarykey(){
        if($this->passed_pk):
            return [\powerorm\checks\check_error($this,
                sprintf("'primary_key' is not a valid argument for a %s.", get_class($this)))];
        endif;
        return [];
    }

    public function options(){
        $opts = parent::options();
        unset($opts['passed_unique']);
        unset($opts['passed_pk']);
        unset($opts['upload_to']);
        return $opts;
    }

    public function form_field(){

        $opts = parent::form_field();
        $opts['upload_to'] = $this->upload_to;
        return $opts;

    }
}

class ImageField extends FileField{

    public function form_type(){
        return 'image';
    }
}

/**
 * Class TextField
 */
class TextField extends Field{

    public function __construct($field_options = []){
        parent::__construct($field_options);

        $this->unique = FALSE;
        $this->db_index = FALSE;
    }

    public function db_type(){
        return 'TEXT';
    }

    public function form_type(){
        return 'textarea';
    }

}

class DecimalField extends Field{
    public $max_digits;
    public $decimal_places;

    public function __construct($field_options = []){
        parent::__construct($field_options);
    }

    public function db_type(){
        return sprintf('DECIMAL(%1$s, %2$s)', $this->max_digits, $this->decimal_places);
    }

    public function form_type(){
        return 'number';
    }

    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks,  $this->_decimal_places_check());
        $checks = $this->add_check($checks,  $this->_check_max_digits());

        return $checks;
    }
    public function _decimal_places_check(){
        if(empty($this->decimal_places)):
            return [check_error($this, sprintf("%s expects 'decimal_place' attribute to be set", get_class($this)))];
        endif;

        if(!is_numeric($this->decimal_places) && $this->decimal_places < 0):
            return [check_error($this,
                sprintf("%s expects 'decimal_place' attribute to be a positive integer", get_class($this)))];
        endif;

        return [];
    }

    public function _check_max_digits(){
        if(empty($this->max_digits)):
            return [check_error($this, sprintf("%s expects 'max_digits' attribute to be set", get_class($this)))];
        endif;

        if(!is_numeric($this->max_digits) && $this->decimal_places < 0):
            return [check_error($this,
                sprintf("%s expects 'max_digits' attribute to be a positive integer", get_class($this)))];
        endif;

        return [];
    }
}

/**
 * Class IntegerField
 */
class IntegerField extends Field{

    public $default;

    public function __construct($options=[]){
        parent::__construct($options);
        if(!$this->signed || $this->signed == 'UNSIGNED'):
            $this->signed = "UNSIGNED";
        else:
            $this->signed = "SIGNED";
        endif;
    }

    public function db_type(){
        return 'INT';
    }

    public function form_type(){
        return 'number';
    }
}

/**
 * Class AutoField
 */
class AutoField extends IntegerField{

    public function __construct($options=[]){
        parent::__construct($options);
        $this->auto = TRUE;
        $this->signed = FALSE;
    }
}

class BooleanField extends Field{
    public $default = FALSE;
    public function db_type(){
        return "BOOLEAN";
    }

    public function form_type(){
        return 'radio';
    }
}

class DateTimeField extends Field{
    public $on_creation=FALSE;
    public $on_update=FALSE;

    public function __construct($options=[]){
        parent::__construct($options);
        if($this->on_creation && $this->on_update):
            throw new OrmExceptions(sprintf('%s expects either `on_creation` or `on_update` to be set and not both', $this->name));
        endif;
    }
    public function db_type(){
        return "DATETIME";
    }
}

class DateField extends DateTimeField{
    public function db_type(){
        return "DATE";
    }
}

class TimeField extends DateTimeField{

    public function db_type(){
        return 'TIME';
    }
}