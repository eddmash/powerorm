<?php
/**
 * The Model Field
 */

/**
 *
 */
namespace eddmash\powerorm\model\field;

use eddmash\powerorm\Contributor;
use eddmash\powerorm\DeConstructable;
use eddmash\powerorm\form\fields as form_fields;
use eddmash\powerorm\helpers\Strings;
use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\model\field\relation\RelationObject;
use eddmash\powerorm\NOT_PROVIDED;
use eddmash\powerorm\Object;

/**
 * Interface FieldInterface
 * @package eddmash\powerorm\model\field
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface FieldInterface extends DeConstructable, Contributor
{

    /**
     * Returns the database column data type for the Field, taking into account the connection.
     *
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function db_type($connection);

    /**
     * Convert the value to a php value
     * @param $value
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function to_php($value);

    /**
     * Returns a powerorm.form.Field instance for this database Field.
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function formfield($kwargs = []);

    /**
     * Method called prior to prepare_value_for_db() to prepare the value before being saved
     * (e.g. for DateField.auto_now).
     *
     * model_instance is the instance this field belongs to and add is whether the instance is being saved to the
     * database for the first time.
     *
     * It should return the value of the appropriate attribute from model_instance for this field.
     * The attribute name is in $this->name (this is set up by Field).
     *
     * @param $model
     * @param bool $add is whether the instance is being saved to the database for the first time.
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function pre_save($model, $add);

    /**
     * value is the current value of the model’s attribute, and the method should return data in a format that has been
     * prepared for use as a parameter in a query.ie. in the database
     *
     * @param $value
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_value($value);

    /**
     * Converts value to a backend-specific value.
     * By default it returns value if prepared=True and prepare_value() if is False.
     *
     * @param $value
     * @param $connection
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_value_for_db($value, $connection, $prepared = false);

    /**
     * Same as the prepare_value_for_db(), but called when the field value must be saved to the database.
     *
     * By default returns prepare_value_for_db().
     *
     * @param $value
     * @param $connection
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_value_before_save($value, $connection);

    /**
     * Converts a value as returned by the database to a PHP object. It is the reverse of prepare_value().
     *
     * This method is not used for most built-in fields as the database backend already returns the correct PHP type,
     * or the backend itself does the conversion.
     *
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function from_db_value();
}

/**
 * This class represents a column in the database table.
 *
 * This class should not be instantiated
 *
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Field extends Object implements FieldInterface
{
    const BLANK_CHOICE_DASH = ["" => "---------"];

    /**
     * @ignore
     * @var null
     */
    public $name = null;


    /**
     * The maximum length (in characters) of the field.
     * @var
     */
    public $max_length;

    /**
     * Indicate if this is an inverse relation field.
     * @var bool
     */
    public $inverse = false;

    /**
     * @ignore
     * @var bool
     */
    public $M2M = false;

    /**
     * @ignore
     * @var bool
     */
    public $M2O = false;

    /**
     * @ignore
     * @var bool
     */
    public $O2O = false;

    /**
     * A human-readable name for the field. If the verbose name isn’t given, Powerorm will automatically create it using
     * the field’s attribute name, converting underscores to spaces.
     * @var string
     */
    public $verbose_name = null;

    /**
     * @ignore
     * @var string
     */
    public $type;

    /**
     * If True, powerorm will store empty values as NULL in the database. Default is False i.e NOT NULL
     *
     * @var bool
     */
    public $null = false;

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
    public $unique = false;

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
    public $primary_key = false;

    /**
     * The default value for the field.
     * @var
     */
    public $default;

    /**
     * @ignore
     * @var null
     */
    public $db_column = null;

    /**
     * If True, this field will be indexed.
     * @var null
     */
    public $db_index = false;

    /**
     * if this is a relationship field, this hold the Relationship object that this field represents
     * @var RelationObject
     */
    public $relation = null;

    /**
     * Indicates if this field is  relationship field.
     * @var bool
     */
    public $is_relation = false;

    /**
     * Model that this field is attached to.
     * @var BaseModel
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
    public $form_blank = false;

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
    public function __construct($field_options = [])
    {
        // if some passes type remove it,
        // we don't people breaking our perfect flow of things.
        if (isset($field_options['type'])):
            unset($field_options['type']);
        endif;

        $this->default = NOT_PROVIDED::instance();

        $this->constructor_args = $field_options;

        // replace the default options with the ones passed in.
        foreach ($field_options as $key => $value) :
            // only replace those that exist do not set new ones
            if ($this->has_property($key)):
                $this->{$key} = $value;
        endif;
        endforeach;

        // null status
        if ($this->primary_key):
            $this->null = false;
        endif;

        if (!in_array('form_blank', $field_options)):
            $this->form_blank = $this->null;
        endif;
    }

    public static function instance($opts)
    {
        return new static($opts);
    }

    /**
     * Calculates the actual column name in the database, especially useful for foreign keys
     * @return string
     */
    public function db_column_name()
    {
        return $this->standard_name($this->name);
    }

    /**
     *
     * @ignore
     */
    public function __validate_name()
    {
    }

    /**
     * Returns all the necessary items needed for recreation of the field again.
     * @return array
     */
    public function skeleton()
    {
        $path = '';
        $alias = 'model_fields';

        if (Strings::starts_with($this->full_class_name(), 'eddmash\powerorm\model\field')):
            $path = sprintf('eddmash\powerorm\model\field as %s', $alias);
        endif;


        return [
            'constructor_args' => $this->constructor_args(),
            'path' => $path,
            'full_name' => $this->full_class_name(),
            'name' => sprintf('%1$s\%2$s', $alias, $this->get_class_name())
        ];
    }

    public function db_params($connection)
    {
        return [
            'type' => $this->db_type($connection)
        ];
    }

    /**
     * Returns all the parameters that were passed to the constructor on initialization
     * @return mixed
     */
    public function constructor_args()
    {
        $this->constructor_args = array_change_key_case($this->constructor_args, CASE_LOWER);

        $defaults = [
            "primary_key" => false,
            "max_length" => null,
            "unique" => false,
            "null" => false,
            "db_index" => false,
            "default" => new NOT_PROVIDED,
        ];
//
        foreach ($defaults as $name => $default) :
            $value = ($this->has_property($name)) ? $this->{$name} : $default;

        if ($name == 'default' && !$value instanceof NOT_PROVIDED):
                $this->constructor_args[$name] = $value; elseif ($value != $default && !array_key_exists(strtolower($name), $this->constructor_args)):

                $this->constructor_args[$name] = $value;

        endif;
        endforeach;

        return [$this->constructor_args];
    }

    /**
     * @inheritdoc
     */
    public function contribute_to_class($field_name, $model_obj)
    {
        $this->container_model = $model_obj;
        $this->set_from_name($field_name);
        $model_obj->load_field($this);
        $model_obj->meta->add_field($this);
    }

    public function set_from_name($name)
    {
        $this->name = $name;
        $this->db_column = $this->db_column_name();

        if (empty($this->verbose_name)):
            $this->verbose_name = ucwords(str_replace("_", " ", $name));
        endif;
    }

    /**
     * returns the constraint name especially in relationship fields.
     * @ignore
     * @param string $prefix
     * @return string
     */
    public function constraint_name($prefix)
    {
        if (empty($prefix)):
            return '';
        endif;

        return sprintf('%1$s_%2$s_%3$s', $prefix, strtolower($this->name), mt_rand());
    }


    /**
     * return the database column that this field represents.
     * @return string
     */
    public function db_type($connection)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function formfield($kwargs = [])
    {
        $field_class = form_fields\CharField::full_class_name();

        $kwargs = array_change_key_case($kwargs, CASE_LOWER);

        $defaults = [
            'required' => !$this->form_blank,
            'label' => $this->verbose_name,
            'help_text' => $this->help_text,
        ];

        if ($this->has_default()):
            $defaults['initial'] = $this->get_default();
        endif;


        if ($this->choices):
            $include_blank = true;

        if ($this->form_blank || empty($this->has_default()) || !in_array('initial', $kwargs)):
                $include_blank = false;
        endif;

        $defaults['choices'] = $this->get_choices(['include_blank' => $include_blank]);
        $defaults['coerce'] = [$this, 'to_php'];

        if (array_key_exists('form_choices_class', $kwargs)):
                $field_class = $kwargs['form_choices_class']; else:
                $field_class = form_fields\TypedChoiceField::full_class_name();
        endif;

        endif;

        if (array_key_exists('field_class', $kwargs)):
            $field_class = $kwargs['field_class'];
        unset($kwargs['field_class']);
        endif;

        $defaults = array_merge($defaults, $kwargs);

        return new $field_class($defaults);
    }

    public function to_php($value)
    {
        return $value;
    }

    /**
     * Tells us if the default value is set
     */
    public function has_default()
    {
        return !$this->default instanceof NOT_PROVIDED;
    }

    public function is_unique()
    {
        return $this->unique || $this->primary_key;
    }

    public function is_inverse()
    {
        return $this->inverse;
    }

    public function get_default()
    {
        return $this->default;
    }

    /**
     * @ignore
     * @return string
     */
    public function __toString()
    {
        return $this->container_model->get_class_name() . '->' . $this->name;
    }

    /**
     * @ignore
     * @param $context
     * @param $value
     */
    public function clean($context, $value)
    {
    }

    /**
     * Should return a list of \eddmash\powerorm\checks\Message instances. used in migrations.
     * @return array
     */
    public function check()
    {
        return [];
    }

    /**
     * @ignore
     * @param $checks
     * @param $new_check
     * @return array
     */
    public function add_check($checks, $new_check)
    {
        if (!empty($new_check)):
            $checks = array_merge($checks, $new_check);
        endif;
        return $checks;
    }

    /**
     * @ignore
     */
    public function validate()
    {
    }

    public function deep_clone()
    {
        $skel = $this->skeleton();
        $constructor_args = array_pop($skel['constructor_args']);
        $class_name = $skel['full_name'];
        return new $class_name($constructor_args);
    }

    /**
     * Use to store this fields results, mostly used in relational fields
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_cache_name()
    {
        return sprintf("_%s_cache", $this->name);
    }

    /**
     * Returns choices with a default blank choices included, for use as SelectField choices for this field.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_choices($opts = [])
    {
        $include_blank_dash = (array_key_exists('include_blank', $opts)) ? $opts['include_blank'] == false : true;

        $first_choice = [];
        if ($include_blank_dash):
            $first_choice = self::BLANK_CHOICE_DASH;
        endif;

        if (!empty($this->choices)):
            return array_merge($first_choice, $this->choices);
        endif;

        // load from relationships todo
    }

    public function pre_save($model, $add)
    {
        // TODO: Implement pre_save() method.
    }

    public function prepare_value($value)
    {
        // TODO: Implement prepare_value() method.
    }

    public function prepare_value_for_db($value, $connection, $prepared = false)
    {
        // TODO: Implement prepare_value_for_db() method.
    }

    public function from_db_value()
    {
        // TODO: Implement from_db_value() method.
    }


    public function prepare_value_before_save($value, $connection)
    {
        return $this->prepare_value_for_db($value, $connection);
    }


    public function __debugInfo()
    {
        $model = [];
        foreach (get_object_vars($this) as $name => $value) :
            if (in_array($name, ['container_model', 'relation', 'constructor_args'])):
                if (is_subclass_of($value, Object::full_class_name())):

                    $model[$name] = $value->get_class_name();

        endif;
        continue;
        endif;
        $model[$name] = $value;
        endforeach;

        return $model;
    }
}
