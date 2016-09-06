<?php

namespace powerorm\model;

use powerorm\Contributor;
use powerorm\model\field\AutoField;
use powerorm\model\field\RelatedField;
use powerorm\Object;

/**
 * Class Meta.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Meta extends Object implements Contributor
{
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

    // todo
    public $unique_together = [];

    /**
     * Indicates if model was auto created by the orm e.g. intermediary model for many to many relationship.
     *
     * @var bool
     */
    public $auto_created = false;

    public function add_field($field_obj)
    {
        $this->fields[$field_obj->name] = $field_obj;
        $this->set_pk($field_obj);

        if ($field_obj instanceof RelatedField):
            $this->relations_fields[$field_obj->name] = $field_obj; else:
            $this->local_fields[$field_obj->name] = $field_obj;
        endif;

        $this->load_inverse_field($field_obj);
    }

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
     *
     * @param BaseModel $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare(BaseModel $model)
    {
        if (empty($this->primary_key)):
            $model->add_to_class('id', new AutoField());
        endif;
    }

    public function contribute_to_class($name, $obj)
    {
        $obj->{$name} = $this;
    }

    public function can_migrate()
    {
        return $this->managed && !$this->proxy;
    }

    /**
     * Get all fields that connect to this model inversely not directly i.e the field just point to this model but its
     * not defined on the model e.g. by usingg hasmanyfield() or hasonefield().
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_reverse_fields()
    {
        $app_models = $this->orm_instance()->get_registry()->get_models();

        $reverse = [];
        foreach ($app_models as $name => $app_model) :
            foreach ($app_model->meta->relations_fields as $fname => $field) :

                if ($field->is_relation &&
                    !empty($field->relation->model) &&
                    $field->relation->model === $this->model_name):
                        $reverse[$field->name] = $field;
        endif;
        endforeach;

        endforeach;

        return $reverse;
    }
}
