<?php
/**
 * The Model Field
 */

/**
 *
 */
namespace powerorm\model\field;

use powerorm\checks\Checks;
use powerorm\Contributor;
use powerorm\DeConstruct;
use powerorm\exceptions\OrmExceptions;
use powerorm\NOT_PROVIDED;
use powerorm\Object;

/**
 * Interface FieldInterface
 * @package powerorm\model\field
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface FieldInterface extends DeConstruct, Contributor{

    /**
     * return the database column that this field represents.
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
     public function db_type();

    /**
     * Returns a powerorm.form.Field instance for this database Field.
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function formfield($kwargs=[]);
}

/**
 * This class represents a column in the database table.
 *
 * This class should not be instantiated
 *
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Field extends Object implements FieldInterface{

    /**
     * @ignore
     * @var null
     */
    public $name=Null;


    /**
     * The maximum length (in characters) of the field.
     * @var
     */
    public $max_length;

    /**
     * @ignore
     * @var bool
     */
    public $inverse = FALSE;

    /**
     * @ignore
     * @var bool
     */
    public $M2M=FALSE; 

    /**
     * @ignore
     * @var bool
     */
    public $M2O=FALSE;

    /**
     * @ignore
     * @var bool
     */
    public $O2O=FALSE;

    /**
     * A human-readable name for the field. If the verbose name isn’t given, Powerorm will automatically create it using
     * the field’s attribute name, converting underscores to spaces.
     * @var string
     */
    public $verbose_name = NULL;

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
    public $db_index=FALSE;

    public $relation = NULL;

    public $is_relation = FALSE;

    /**
     * Model that this field is attached to.
     * @var
     */
    public $container_model;

    // =====================  form specifics

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
     * @var
     */
    private $constructor_args;

    /**
     * Takes in options to determine how to create the field.
     * @param array $field_options the options to use.
     */
    public function __construct($field_options = []){
        // if some passes type remove it,
        // we don't people breaking our perfect flow of things.
        if(isset($field_options['type'])):
            unset($field_options['type']);
        endif;
        $this->default = new NOT_PROVIDED;

        $this->constructor_args = $field_options;

        // replace the default options with the ones passed in.
        foreach ($field_options as $key=>$value) :
            // only replace those that exist do not set new ones
            if($this->has_property($key)):
                $this->{$key} = $value;
            endif;
        endforeach;

        // null status
        if($this->primary_key):
            $this->null = FALSE;
        endif;

        if(!in_array('form_blank', $field_options)):
            $this->form_blank = $this->null;
        endif;

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
            'constructor_args'=>$this->constructor_args(),
            'path'=>get_class($this)
        ];
    }
 
    public function db_params($connection)
    {
        return [
            'type'=> $this->db_type()
        ];
    }

    /**
     * Returns all the parameters that were passed to the constructor on initialization
     * @return mixed
     */
    public function constructor_args(){
        $this->constructor_args = array_change_key_case($this->constructor_args, CASE_LOWER);

        $defaults = [
            "primary_key"=>False,
            "max_length"=>NULL,
            "unique"=>False,
            "null"=>False,
            "db_index"=>False,
            "default"=>new NOT_PROVIDED,
        ];
//
        foreach ($defaults as $name=>$default) :
            $value = ($this->has_property($name)) ? $this->{$name} : $default;

            if($name == 'default' && ! $value instanceof NOT_PROVIDED):
                $this->constructor_args[$name] = $value;
            elseif($value != $default && !array_key_exists(strtolower($name), $this->constructor_args)):

                $this->constructor_args[$name] = $value;

            endif;
        endforeach;

        return [$this->constructor_args];
    }

    /**
     * inheritdoc
     */
    public function contribute_to_class($field_name, $model_obj){
        $this->container_model = $model_obj;
        $this->set_from_name($field_name);
        $model_obj->load_field($this);
        $model_obj->meta->add_field($this);

    }

    public function set_from_name($name){
        $this->name = $name;
        $this->db_column = $this->db_column_name();

        if(empty($this->verbose_name)):
            $this->verbose_name = ucwords(str_replace("_", " ", $name));
        endif;
    }

    /**
     * returns the constraint name especially in relationship fields.
     * @ignore
     * @param string $prefix
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
        return NULL;
    }

    /**
     * @inheritdoc
     */
    public function formfield($kwargs=[]){

        $kwargs = array_change_key_case($kwargs, CASE_LOWER);

        $defaults = [
            'required'=> ! $this->form_blank,
            'label'=>$this->verbose_name,
            'help_text'=>$this->help_text,
        ];

        if($this->has_default()):
            $defaults['initial'] = $this->get_default();
        endif;
        
        if($this->choices):
            //todo
        endif;


        $field_class = '\powerorm\form\fields\CharField';
        if(array_key_exists('field_class', $kwargs)):
            $field_class = $kwargs['form_class'];
            unset($kwargs['form_class']);
        endif;

        $defaults = array_merge($defaults, $kwargs);
        return new $field_class($defaults);
    }

    /**
     * Tells us if the default value is set
     */
    public function has_default(){
        return $this->default === (new NOT_PROVIDED());
    }

    public function is_unique(){
        return $this->unique || $this->primary_key;
    }
    
    public function get_default(){
        return $this->default;
    }
 
    /**
     * @ignore
     * @return string
     */
    public function __toString(){
        return $this->container_model->get_class_name().'->'.$this->name;
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

    public function deep_clone(){
        $skel = $this->skeleton();
        $constructor_args =array_pop($skel['constructor_args']);
        $path =$skel['path'];
        return new $path($constructor_args);
    }

    /**
     * Use to store this fields results, mostly used in relational fields
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_cache_name(){
        return sprintf("_%s_cache", $this->name);
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

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){

        parent::__construct($field_options);
    }

    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return 'VARCHAR';
    }

    /**
     * {@inheritdoc}
     */
    public function form_type(){

        $type = 'text';

        if(!empty($this->choices)):
            $type = 'dropdown';
        endif;

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_max_length_check());
        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _max_length_check(){
        if(empty($this->max_length)):
            return [
                Checks::error([
                    "message"=>"Charfield requires `max_length` to be set",
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E120"
                ])
            ];
        endif;

        if(!empty($this->max_length) && $this->max_length < 0):
            return [
                Checks::error([
                    "message"=>'Charfield requires `max_length` to be a positive integer',
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E121"
                ])
            ];
        endif;
        return [];
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

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        //  254 in order to be compliant with RFC3696/5321
        $field_options['max_length'] = 254;
        parent::__construct($field_options);
    }

    /**
     * {@inheritdoc}
     */
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
    /**
     * @ignore
     * @var bool
     */
    protected $passed_pk;

    /**
     * @ignore
     * @var bool
     */
    protected $passed_unique;

    /**
     * The path relative to the application base_url where the files will be uploaded
     * @var
     */
    public $upload_to;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        $field_options['max_length'] = 100;
        $this->passed_pk = (array_key_exists('primary_key', $field_options))? TRUE: FALSE;
        $this->passed_unique = (array_key_exists('unique', $field_options))? TRUE: FALSE;
        parent::__construct($field_options);
    }


    /**
     * {@inheritdoc}
     */
    public function form_type(){
        return 'file';
    }


    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();

        $checks = $this->add_check($checks, $this->_check_primarykey());

        $checks = $this->add_check($checks, $this->_check_unique());
        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_unique(){
        if($this->passed_unique):
            return [
                Checks::error([
                    "message"=>sprintf("'unique' is not a valid argument for a %s.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E200'
                ])
            ];
        endif;
        return [];
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_primarykey(){
        if($this->passed_pk):

            return [
                Checks::error([
                    "message"=>sprintf("'primary_key' is not a valid argument for a %s.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E201'
                ])
            ];
        endif;

        return [];
    }



    /**
     * {@inheritdoc}
     */
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


    /**
     * {@inheritdoc}
     */
    public function form_type(){
        return 'image';
    }
}

/**
 * A large text field. The default form widget for this field is a 'Textarea'.
 */
class TextField extends Field{


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);

        $this->unique = FALSE;
        $this->db_index = FALSE;
    }


    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return 'TEXT';
    }


    /**
     * {@inheritdoc}
     */
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
    /**
     * The maximum number of digits allowed in the number.
     * Note that this number must be greater than or equal to decimal_places.
     *
     * @var
     */
    public $max_digits;

    /**
     * The number of decimal places to store with the number.
     * @var
     */
    public $decimal_places;


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);
    }


    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return 'DECIMAL';
    }


    /**
     * {@inheritdoc}
     */
    public function form_type(){
        return 'number';
    }


    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks,  $this->_decimal_places_check());
        $checks = $this->add_check($checks,  $this->_check_max_digits());

        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _decimal_places_check(){
        if(empty($this->decimal_places)):
            return [
                Checks::error([
                    "message"=>sprintf("%s expects 'decimal_place' attribute to be set.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E130'
                ])
            ];
        endif;

        if(!is_numeric($this->decimal_places) || $this->decimal_places < 0):
            return [
                Checks::error([
                    "message"=>sprintf("%s expects 'decimal_place' attribute to be a positive integer.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E131'
                ])
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_max_digits(){
        if(empty($this->max_digits)):
            return [
                Checks::error([
                    "message"=>sprintf("%s expects 'max_digits' attribute to be set.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E132'
                ])
            ];
        endif;

        if(!is_numeric($this->max_digits) || $this->max_digits < 0):
            return [
                Checks::error([
                    "message"=>sprintf("%s expects 'max_digits' attribute to be a positive integer", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E133'
                ])
            ];
        endif;
        
        // ensure max_digits is greater than decimal_places
        if($this->max_digits < $this->decimal_places):
            return [
                Checks::error([
                    "message"=>sprintf("%s expects 'max_digits' to be greater than 'decimal_places'", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E134'
                ])
            ];
        endif;

        return [];
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
     * else it will create an unsigned integer 0 to -2147483647.
     *
     * @var bool
     */
    public $signed=NULL;


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        parent::__construct($field_options);

    }

    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return 'INT';
    }

    /**
     * {@inheritdoc}
     */
    public function form_type(){
        return 'number';
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


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        parent::__construct($field_options);
        $this->auto = TRUE;
        $this->unique = TRUE;
        $this->primary_key = TRUE;
    }


    /**
     * {@inheritdoc}
     */
    public function contribute_to_class($property_name, $model){
        assert($model->meta->has_auto_field!==TRUE,
            sprintf("%s has more than one AutoField, this is not allowed", $model->meta->model_name));
        parent::contribute_to_class($property_name, $model);

        $model->meta->has_auto_field = TRUE;
        $model->meta->auto_field = $this;
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

    /**
     * {@inheritdoc}
     */
    public $default = FALSE;


    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return "BOOLEAN";
    }


    /**
     * {@inheritdoc}
     */
    public function form_type(){
        return 'radio';
    }
}

/**
 * Create a DateTime column i.e. date and timestamp.
 * @package powerorm\model\field
 */
class DateTimeField extends Field{
    /**
     * Automatically set the field to now when the object is first created. Useful for creation of timestamps.
     * Note that the current date is always used;
     * it’s not just a default value that you can override.
     * @var bool
     */
    public $on_creation=FALSE;
    /**
     * Automatically set the field to now every time the object is saved.
     * Useful for “last-modified” timestamps. Note that the current date is always used;
     * it’s not just a default value that you can override.
     * @var bool
     */
    public $on_update=FALSE;

    /**
     * {@inheritdoc}
     * @param array $field_options
     * @throws OrmExceptions
     */
    public function __construct($field_options=[]){
        parent::__construct($field_options);
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
}

/**
 * Creates a Date column i.e. just the date not timestamp.
 * @package powerorm\model\field
 */
class DateField extends DateTimeField{

    /**
     * @ignore
     * @return string
     */
    public function db_type(){
        return "DATE";
    }
}

/**
 * Creates a Timestamp column i.e no date.
 * @package powerorm\model\field
 */
class TimeField extends DateTimeField{

    /**
     * @ignore
     * @return string
     */
    public function db_type(){
        return 'TIME';
    }
}