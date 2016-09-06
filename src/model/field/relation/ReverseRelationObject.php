<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/6/16
 * Time: 1:19 AM.
 */
namespace eddmash\powerorm\model\field\relation;

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
