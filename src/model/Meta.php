<?php
namespace eddmash\powerorm\model;

use eddmash\powerorm\app\Registry;
use eddmash\powerorm\Contributor;
use eddmash\powerorm\model\field\AutoField;
use eddmash\powerorm\model\field\Field;
use eddmash\powerorm\model\field\RelatedField;
use eddmash\powerorm\Object;
use Orm;

/**
 * Class Meta
 * @package eddmash\powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Meta extends Object implements Contributor
{
    const DEFAULT_NAMES = ['registry', 'verbose_name'];

    public $model_name;
    public $has_auto_field = false;
    public $auto_field;
    public $db_table;
    public $primary_key;
    public $fields = [];
    public $relations_fields = [];
    public $local_fields = [];
    public $inverse_fields = [];
    public $trigger_fields = [];
    public $managed;
    public $proxy;
    public $abstract;

    /**
     * Holds the registry the model is attached to.
     * @var Registry
     */
    public $registry;

    // todo
    public $unique_together = [];

    /**
     * This will hold items that will be overriden in the current meta instance.
     * @var array
     */
    private $overrides = [];

    /**
     * Indicates if model was auto created by the orm e.g. intermediary model for many to many relationship.
     * @var bool
     */
    public $auto_created = false;


    public function __construct($overrides = [])
    {
        $this->overrides = $overrides;
//        $this->registry = BaseOrm::instance()->get_registry();
    }

    public function add_field($field_obj)
    {
        $this->fields[$field_obj->name] = $field_obj;
        $this->set_pk($field_obj);

        if ($field_obj instanceof RelatedField):
            $this->relations_fields[$field_obj->name] = $field_obj;
        else:
            $this->local_fields[$field_obj->name] = $field_obj;
        endif;

        $this->load_inverse_field($field_obj);
    }

    /**
     * @param Field $field_obj
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     */
    public function load_inverse_field($field_obj)
    {
        if ($field_obj->is_inverse()):
            $this->inverse_fields[$field_obj->name] = $field_obj;
        endif;
    }

    public function get_field($field_name)
    {
        return (array_key_exists($field_name, $this->fields)) ? $this->fields[$field_name] : null;
    }

    public function set_pk($field_obj)
    {
        if ($field_obj->primary_key):
            $this->primary_key = $field_obj;
        endif;
    }

    public function get_fields_names()
    {
        return array_keys($this->fields);
    }

    /**
     * Makes sure the model is ready for use.
     * @param BaseModel $model
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare(BaseModel $model)
    {
        if (empty($this->primary_key)):
            $model->add_to_class('id', new AutoField());
        endif;
    }

    /**
     * @param string $name
     * @param BaseModel $obj
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contribute_to_class($name, $obj)
    {
        $obj->{$name} = $this;
        $this->model_name = $this->standard_name($obj->get_class_name());
        $this->db_table = $obj->get_table_name();
        $this->managed = $obj->is_managed();
        $this->abstract = $this->_is_abstract($obj);
        $this->proxy = $obj->is_proxy();

        foreach (self::DEFAULT_NAMES as $default_name) :
            if (array_key_exists($default_name, $this->overrides)):
                $this->{$default_name} = $this->overrides[$default_name];
            endif;
        endforeach;
    }

    public function can_migrate()
    {
        return $this->managed && !$this->proxy;
    }

    private function _is_abstract($obj)
    {
        $reflection = new \ReflectionClass($obj);
        return $reflection->isAbstract();
    }

    /**
     * Get all fields that connect to this model inversely not directly i.e the field just point to this model but its
     * not defined on the model e.g. by usingg hasmanyfield() or hasonefield().
     *
     * @return array
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_reverse_fields()
    {
        $app_models = Orm::get_registry()->get_models();

        $reverse = [];
        foreach ($app_models as $name => $app_model) :
            foreach ($app_model->meta->relations_fields as $fname => $field) :

                if ($field->is_relation && !empty($field->relation->model) &&
                    $field->relation->model === $this->model_name
                ):

                    $reverse[$field->name] = $field;
                endif;
            endforeach;

        endforeach;

        return $reverse;
    }
}
