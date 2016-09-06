<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:56 AM
 */

namespace eddmash\powerorm\model\field\accessor;

class Accessor implements \ArrayAccess, \IteratorAggregate
{
    public $model;
    public $field;
    public $cache_name;

    public function __construct($model, $field)
    {
        $this->model = $model;
        $this->field = $field;
        $this->cache_name = $field->get_cache_name();
    }

    public static function instance($model, $field)
    {
        return new static($model, $field);
    }

    public function getIterator()
    {

        // TODO: Implement offsetUnset() method.
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }
}
