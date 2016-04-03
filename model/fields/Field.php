<?php
namespace powerorm\model\field;

use powerorm\exceptions\OrmExceptions;
use powerorm\form;


/**
 * This class represents a column in the database table.
 *
 * This is the parent class all the fields and should not be instantiated
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Field{

    /**
     * @ignore
     * @var null
     */
    public $name=Null;

    /**
     * @ignore
     * @var string
     */
    public $type;

    /**
     * @ignore
     * @var
     */
    public $constraint_name;

    /**
     * If True, powerorm will store empty values as NULL in the database. Default is False.
     *
     * @var bool
     */
    public $null=FALSE;

    /**
     * If True, this field must be unique throughout the table.
     *
     * This is enforced at the database level and by model validation.
     *
     * If you try to save a model with a duplicate value in a unique field,
     *
     * This option is valid on all field types except ManyToManyField, OneToOneField, and FileField.
     *
     * Note that when unique is True, you don’t need to specify db_index, because unique implies the creation of an index.
     *
     * @var bool
     */
    public $unique=FALSE;

    /**
     * If True, this field is the primary key for the model.
     *
     * If you don’t specify primary_key=True for any field in your model, Poweroem will automatically add an AutoField
     * to hold the primary key,
     *
     * so you don’t need to set primary_key=True on any of your fields unless you want to override the default
     * primary-key behavior.
     *
     * primary_key=True implies null=False and unique=True. Only one primary key is allowed on an object.
     *
     * The primary key field is read-only. If you change the value of the primary key on an existing object and then
     * save it, a new object will be created alongside the old one.
     *
     * @var bool
     */
    public $primary_key=FALSE;

    /**
     * The default value for the field.
     * @var
     */
    public $default;

    /**
     * @ignore
     * @var null
     */
    public $db_column=Null;

    /**
     * If True, this field will be indexed.
     * @var null
     */
    public $db_index=Null;

    /**
     * the model that this field belongs to.
     * @ignore
     * @var
     */
    public $container_model;

    // form specifics

    /**
     * If True, the field is allowed to be blank on form. Default is False.
     *
     * Note that this is different than null. null is purely database-related,
     *
     * whereas form_blank is validation-related.
     *
     * If a field has form_blank=True, form validation will allow entry of an empty value.
     *
     * If a field has form_blank=False, the field will be required.
     *
     * @var bool
     */
    public $form_blank=FALSE;

    /**
     * An array consisting of items to use as choices for this field.
     *
     * If this is given, the default form widget will be a select box with these choices instead of the
     * standard text field.
     *
     * The first element in each array is the actual value to be set on the model, and the second element is the
     * human-readable name.
     *
     * For example:
     * $gender_choices = [
     *      'm'=>'Male',
     *      'f'=>'Female',
     * ]
     *
     * $gender =  ORM::CharField(['max_length'=2, 'choices'=$gender_choices])
     *
     * @var
     *
     */
    public $choices;

    /**
     * Set the html form type to use for this form.
     * @var
     */
    public $form_widget;

    /**
     * Used for forms with choices, this set the default option to show
     *
     *
     * @var
     */
    public $empty_label;

    /**
     * Extra “help” text to be displayed with the form widget.
     * It’s useful for documentation even if your field isn’t used on a form.
     *
     * Note that this value is not HTML-escaped in automatically-generated forms.
     * This lets you include HTML in help_text if you so desire.
     *
     * For example:
     *  <pre><code>help_text="Please use the following format: <em>YYYY-MM-DD</em>."</code></pre>
     *
     * @var
     */
    public $help_text;

    /**
     * @ignore
     * @param array $field_options
     */
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
     * @return string
     */
    public function db_column_name(){
        return $this->name;
    }

    /**
     *
     * @ignore
     */
    public function __validate_name(){

    }

    /**
     * Returns all the necessary items needed for recreation of the field again.
     * @return array
     */
    public function skeleton(){
        return [
            'field_options'=>$this->options(),
            'class'=>get_class($this)
        ];
    }

    /**
     * Returns all the options that are necessary to represent this field on the database.
     * @return array
     */
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

        $opts = [];
        $opts['name'] = $this->name;
        $opts['type'] = $this->type;
        $opts['unique'] = $this->unique;
        $opts['null'] = $this->null;
        $opts['default'] = $this->default;
        $opts['primary_key'] = $this->primary_key;
        $opts['db_column'] = $this->db_column;
        $opts['db_index'] = $this->db_index;
        $opts['constraint_name'] = $this->constraint_name;
        $opts['container_model'] = $this->container_model;

        return $opts;
    }

    /**
     * returns the constriain name especially in relationship fields.
     * @param $prefix
     * @return string
     */
    public function constraint_name($prefix){
        if(empty($prefix)):
            return '';
        endif;

        return sprintf('%1$s_%2$s_%3$s', $prefix, strtolower($this->name), mt_rand());
    }

    /**
     * Returns all the options necessary to represent this field on a HTML Form.
     * @return array
     */
    public function form_field(){

        return $this->_prepare_form_field();
    }

    /**
     * @ignore
     * @return array
     */
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
        $opts['help_text'] = $this->help_text;

        return $opts;
    }

    /**
     * return the database column that this field represents.
     * @return string
     */
    public function db_type(){
        return '';
    }

    /**
     * returns the HTML Form type that this field represents.
     * @return string
     */
    public function form_type(){

        return 'text';
    }

    public function __set($key, $value){
        if($key==='value'):
            $this->{'value'} = $value;
        endif;
    }

    /**
     * @ignore
     * @return string
     */
    public function __toString(){
        return ''.$this->name;
    }

    /**
     * @ignore
     * @param $context
     * @param $value
     */
    public function clean($context, $value){

    }

    /**
     * Should return a list of \powerorm\checks\Message instances. used in migrations.
     * @return array
     */
    public function check(){
        return [];
    }

    /**
     * @ignore
     * @param $checks
     * @param $new_check
     * @return array
     */
    public function add_check($checks, $new_check){
        if(!empty($new_check)):
            $checks = array_merge($checks, $new_check);
        endif;
        return $checks;
    }

    /**
     * @ignore
     */
    public function validate(){

    }


}

/**
 * A string field, for small- to large-sized strings. i.e. it creates SQL varchar column .
 *
 * For large amounts of text, use TextField.
 *
 * The default form input type is 'text'.
 *
 * CharField has one required argument:
 *   - max_length The maximum length (in characters) of the field.
 *          The max_length is enforced at the database level and in form validation.
 */
class CharField extends Field{

    public $max_length;

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

    public function options(){
        $opts = parent::options();

        $opts['max_length'] = $this->max_length;

        return $opts;
    }

}

/**
 * Inherits from CharField.
 * Just like CharField but ensure the input provided is a valid email.
 *
 * - default max_length was increased from 75 to 254 in order to be compliant with RFC3696/5321.
 *
 * @package powerorm\model\field
 */
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
 * Inherits from CharField.
 *
 * A file upload field.
 * - The primary_key and unique arguments are not supported on this field.
 *
 * FileField has one optional argument:
 *   - upload_to this is the path relative to the application base_url where the files will be uploaded.
 *
 * @package powerorm\model\field
 */
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

    public function form_field(){

        $opts = parent::form_field();
        $opts['upload_to'] = $this->upload_to;
        return $opts;

    }
}

/**
 * Inherits all attributes and methods from FileField, but also validates that the uploaded object is a valid image.
 *
 * @package powerorm\model\field
 */
class ImageField extends FileField{

    public function form_type(){
        return 'image';
    }
}

/**
 * A large text field. The default form widget for this field is a 'Textarea'.
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

/**
 * A fixed-precision decimal number. SQl column DECIMAL(M,D)
 *
 * Has two required arguments:
 *
 * - max_digits
 *
 *    The maximum number of digits allowed in the number. Note that this number must be greater than or equal to decimal_places.
 *
 * - decimal_places
 *
 *    The number of decimal places to store with the number.
 *
 * For example, to store numbers up to 999 with a resolution of 2 decimal places, you’d use:
 *
 * ORM::DecimalField(max_digits=5, decimal_places=2)
 *
 * And to store numbers up to approximately one billion with a resolution of 10 decimal places:
 *
 * ORM::DecimalField(max_digits=19, decimal_places=10)
 *
 * The default form widget for this field is a 'text'.
 *
 * @package powerorm\model\field
 */
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
            return [\powerorm\checks\check_error($this,
                sprintf("%s expects 'decimal_place' attribute to be set", get_class($this)))];
        endif;

        if(!is_numeric($this->decimal_places) || $this->decimal_places < 0):
            return [\powerorm\checks\check_error($this,
                sprintf("%s expects 'decimal_place' attribute to be a positive integer", get_class($this)))];
        endif;

        return [];
    }

    public function _check_max_digits(){
        if(empty($this->max_digits)):
            return [\powerorm\checks\check_error($this,
                sprintf("%s expects 'max_digits' attribute to be set", get_class($this)))];
        endif;

        if(!is_numeric($this->max_digits) || $this->max_digits < 0):
            return [\powerorm\checks\check_error($this,
                sprintf("%s expects 'max_digits' attribute to be a positive integer", get_class($this)))];
        endif;
        
        // ensure max_digits is greater than decimal_places
        if($this->max_digits < $this->decimal_places):
            return [\powerorm\checks\check_error($this,
                sprintf("%s expects 'max_digits' to be greate than 'decimal_places' ", get_class($this)))];
        endif;

        return [];
    }

    public function options(){
        $opts = parent::options();
        $opts['max_digits'] = $this->max_digits;
        $opts['decimal_places'] = $this->decimal_places;
        return $opts;
    }
}

/**
 * Creates and integer column of Values ranging from -2147483648 to 2147483647.
 *
 * The default form widget for this field is a 'number' with a fallback on 'text' on browsers that dont support html5.
 *
 * @package powerorm\model\field
 */
class IntegerField extends Field{

    /**
     * If this options is set to TRUE, it will create a signed integer 0 to 2147483647
     * else it will create an unsigned integer 0 to -2147483647
     * @var bool
     */
    public $signed=FALSE;

    public function __construct($options=[]){
        parent::__construct($options);
        if(!$this->signed || $this->signed == 'UNSIGNED'):
            $this->signed = "UNSIGNED";
        else:
            $this->signed = "SIGNED";
        endif;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function db_type(){
        return 'INT';
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function form_type(){
        return 'number';
    }

    public function options(){

        $opts = parent::options();

        $opts['signed'] = $this->signed;
        return $opts;
    }
}

/**
 * An IntegerField that automatically increments according to available IDs.
 * You usually won’t need to use this directly;
 * a primary key field will automatically be added to your model if you don’t specify otherwise.
 *
 * @package powerorm\model\field
 */
class AutoField extends IntegerField{

    /**
     * @ignore
     * @var bool
     */
    public $auto=FALSE;

    public function __construct($options=[]){
        parent::__construct($options);
        $this->auto = TRUE;
        $this->signed = FALSE;
    }

    public function options(){
        $opts = parent::options();

        $opts['auto'] = $this->auto;
        return $opts;
    }
}

/**
 * A true/false field.
 *
 * The default form widget for this field is a 'radio'.
 *
 * @package powerorm\model\field
 */
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

    /**
     * @ignore
     * @return string
     */
    public function db_type(){
        return "DATETIME";
    }
    
    public function options(){
        $opts = parent::options();
        $opts['on_update'] = $this->on_update;
        $opts['on_creation'] = $this->on_creation;
        return $opts;
    }
}

/**
 * Class DateField
 * @package powerorm\model\field
 */
class DateField extends DateTimeField{
    public function db_type(){
        return "DATE";
    }
}

/**
 * Class TimeField
 * @package powerorm\model\field
 */
class TimeField extends DateTimeField{

    public function db_type(){
        return 'TIME';
    }
}