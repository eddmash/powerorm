<?php
//namespace powerorm\model;

use powerorm\model\OrmExceptions;
use powerorm\model\RelationObject;
use powerorm\form;


/**
 * Class ModelField
 */
class ModelField{
    public $name=Null;
    protected $_ci;
    public $type;
    public $null=TRUE;
    public $unique=FALSE;
    public $max_length=NULL;
    public $primary_key=FALSE;
    public $auto=FALSE;
    public $default=NULL;
    public $signed=NULL;
    public $constraint_name;
    public $db_column=Null;
    public $db_index=Null;
    public $form_blank=FALSE;
    public $choices;
    public $form_widget;
    public $empty_label;

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

        $this->_ci =& get_instance();

        $this->check();
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

    public function check(){

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

        unset($opts['_ci']);
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

        return new form\FormField($this->_prepare_form_field());
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

    public function validate(){

    }


}

/**
 * Class CharField
 */
class CharField extends ModelField{

    public function __construct($options=[]){

        parent::__construct($options);
        if(empty($this->max_length)):
            throw new OrmExceptions("CharField requires `max_length` to be set");
        endif;


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

/**
 * Class TextField
 */
class TextField extends ModelField{

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

class DecimalField extends ModelField{
    public $max_digits;
    public $decimal_places;

    public function __construct($field_options = []){
        parent::__construct($field_options);

        if(empty($this->max_digits)):
            throw new OrmExceptions(sprintf("%s requires `max_digits` to be set", get_class($this)));
        endif;

        if(empty($this->max_digits)):
            throw new OrmExceptions(sprintf("%s requires `decimal_places` to be set", get_class($this)));
        endif;

        if($this->max_digits < $this->decimal_places):
            throw new OrmExceptions(
                sprintf("%s expects `decimal_places` to be less or equal to `max_digits`", get_class($this)));
        endif;
    }

    public function db_type(){
        return sprintf('DECIMAL(%1$s, %2$s)', $this->max_digits, $this->decimal_places);
    }

    public function form_type(){
        return 'number';
    }
}

/**
 * Class IntegerField
 */
class IntegerField extends ModelField{

    public function __construct($options=[]){
        parent::__construct($options);
        if(empty($this->signed)):
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
    }
}

class BooleanField extends ModelField{
    public $default = FALSE;
    public function db_type(){
        return "BOOLEAN";
    }

    public function form_type(){
        return 'checkbox';
    }
}

class DateTimeField extends ModelField{
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

class RelatedField extends ModelField{

    public $related_model;
    public $model;

    protected $on_update;
    protected $on_delete;

    public $M2M=FALSE;
    public $M2O=FALSE;
    public $O2O=FALSE;

    public function __construct($field_options = []){

        if(!array_key_exists('model', $field_options)):
            throw new OrmExceptions(sprintf('%1$s requires a related model', get_class($this)));
        endif;

        parent::__construct($field_options);

        // we are using proxy object for related model because we don't want to run into problems
        // a problem will arise in circular dependacy where model A has foreign key to B, and B has a foreign key to A
        $this->related_model = new RelationObject($this->model);


    }

    public function related_pk(){
        return $this->related_model->meta->primary_key;
    }


}

/**
 * Class ForeignKey
 */
class ForeignKey extends RelatedField{
    public $empty_label  = '---------';
    public $form_display_field;
    public $form_value_field;

    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = TRUE;
    }

    public function form_field(){

        $opts = $this->_prepare_form_field();

        // fetch all the records in the related model
        $opts['type'] = 'dropdown';
        $opts['form_display_field'] = $this->form_display_field;
        $opts['form_value_field'] = $this->form_value_field;
        $opts['choices'] = $this->related_model->all();

        return new form\FormField($opts);
    }

    public function options(){
        $this->type = $this->related_pk()->type;
        $this->signed = $this->related_pk()->signed;
        $this->db_column = $this->db_column_name();
        return parent::options();
    }

    public function db_column_name(){
        return $this->name.'_'.$this->related_model->meta->primary_key->db_column_name();
    }

    public function constraint_name($prefix){
        $prefix = "fk";
        return parent::constraint_name($prefix);
    }

}

/**
 * Class OneToOne
 */
class OneToOne extends ForeignKey{
    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = FALSE;
        $this->O2O = TRUE;
        $this->unique = TRUE;

    }
}

/**
 * Class ManyToMany
 */
class ManyToMany extends RelatedField{

    public $through;

    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2M = TRUE;
        if(array_key_exists('through', $field_options)):
            $this->through = $field_options['through'];
        endif;
    }

}


abstract class InverseRelation{
    public $name;
    public $model_name;

    public function __construct($model_name){
        $this->model_name = $model_name;
    }

    public function queryset(){
        return '';
    }

    public function __get($key){
//        return
    }
}

class HasMany extends InverseRelation{}
class HasOne extends InverseRelation{}