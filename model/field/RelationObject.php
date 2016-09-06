<?php

namespace powerorm\model\field;

use powerorm\Object;

/**
 * Act as a buffer for Relation Fields, to help avoid issues with relations and self referencing.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelationObject extends Object
{
    /**
     * Indicate if this is a reverse relation.
     *
     * @var bool
     */
    public $reverse = false;
    public $auto_created = true;
    public $field = null;
    public $model = null;
    public $field_name = null;
    public $related_name = null;

    public function __construct($opts = [])
    {
        $this->model = $opts['model'];
        $this->field = $opts['field'];
    }

    public function model()
    {
        if ($this->get_registry()->has_model($this->model)) :
            return  $this->get_registry()->get_model($this->model); else:
            return;
        endif;
    }

    public function get_model()
    {
        return $this->get_registry()->get_model($this->model);
    }

    public function is_reverse_relation()
    {
        return $this->reverse;
    }
}

class ManyToOneObject extends RelationObject
{
}

class OneToOneObject extends RelationObject
{
}

class ManyToManyObject extends RelationObject
{
    public $through = null;

    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->through = $opts['through'];
    }
}



abstract class ReverseRelationObject extends RelationObject
{
    /**
     * {@inheritdoc}
     *
     * @var bool
     */
    public $reverse = true;

    /**
     * Which field in relation model connects back to the current model.
     *
     * @var null
     */
    public $mapped_by = null;

    public function __construct($opts = [])
    {
        parent::__construct($opts);

        $this->mapped_by = $opts['mapped_by'];
    }

    public function get_mapped_by()
    {
        return $this->get_model()->meta->get_field($this->mapped_by);
    }
}

class OneToManyObject extends ReverseRelationObject
{
}
