<?php
namespace eddmash\powerorm\model\field\relation;

use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\Object;

/**
 * Act as a buffer for Relation Fields, to help avoid issues with relations and self referencing.
 * but most importantly it hold information about relationship.
 *
 * @package eddmash\powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelationObject extends Object
{

    /**
     * Indicate if this is a reverse relation
     * @var bool
     */
    public $reverse = false;
    public $auto_created = true;

    /**
     * @var Field
     */
    public $field = null;
    /**
     * @var BaseModel
     */
    private $model = null;
    public $field_name = null;
    public $related_name = null;

    public function __construct($opts = [])
    {
        $this->model = $opts['model'];
        $this->field = $opts['field'];
    }

    public function is_reverse_relation()
    {
        return $this->reverse;
    }

    /**
     * @return Field
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function target_field()
    {
        return $this->get_model()->meta->primary_key;
    }

    /**
     * @return BaseModel
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_model()
    {
        return $this->model;
    }

    /**
     * @param BaseModel $model
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function set_model($model)
    {
        $this->model = $model;
    }
}
