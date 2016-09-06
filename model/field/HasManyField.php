<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:10 PM.
 */
namespace powerorm\model\field;

/**
 * This creates a reverse connection to a model that define one-to-many/ many-to-many relationships
 * by using ForeignKey or ManyToMAny Fields.
 */
class HasManyField extends ManyToOneField
{
    use InverseRelation;

    public function __construct($field_options = [])
    {
        $this->M2O = false;
        parent::__construct($field_options);
    }

    public function contribute_to_class($name, $obj)
    {
        Field::contribute_to_class($name, $obj);
        $this->container_model->{$name} = ReverseManyToOneAccessor::instance($this->container_model, $this);
    }
}
