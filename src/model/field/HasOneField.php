<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 7/25/16
 * Time: 2:22 PM.
 */
namespace eddmash\powerorm\model\field;

use eddmash\powerorm\exceptions\OrmExceptions;

/**
 * Creates a reverse connection to a model that defines a one-toone relationship using OneToOne field.
 */
class HasOneField extends ManyToOneField
{
    use InverseRelation;

    public function __construct($field_options = [])
    {
        $this->M2O = false;
        parent::__construct($field_options);

        if (!array_key_exists('mapped_by', $field_options)):
            throw new OrmExceptions(sprintf('%s fields need `mapped_by`', $this->get_class_name()));
        endif;

        $this->relation = new OneToManyObject([
            'model' => $field_options['model'],
            'field' => $this,
            'mapped_by' => $field_options['mapped_by'],
        ]);
    }

    public function db_column_name()
    {
        return sprintf('%s_id', $this->standard_name($this->relation->get_mapped_by()->name));
    }

    public function contribute_to_class($name, $obj)
    {
        Field::contribute_to_class($name, $obj);
        $this->container_model->{$name} = ReverseManyToOneAccessor::instance($this->container_model, $this);
    }
}
