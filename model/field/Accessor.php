<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:56 AM
 */

namespace powerorm\model\field;


class Accessor implements \ArrayAccess, \IteratorAggregate{

    public $model;
    public $field;
    public $cache_name;

    public function __construct($model, $field){
        $this->model = $model;
        $this->field = $field;
        $this->cache_name = $field->get_cache_name();
    }

    public static function instance($model, $field){
        return new static($model, $field);
    }

    public function getIterator(){

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

/**
 * Accessing from the many side to the one side
 *
 * Class ForwardManyToOneAccesor
 * @package powerorm\model\field
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ForwardManyToOneAccessor extends Accessor{

    public function __get($name){

        return $this->_fetch_related_record($name);
    }

    public function __set($name, $value){
    }

    public function __toString(){

        return (string)$this->_fetch_related_record();
    }

    public function _fetch_related_record($name=NULL){

        $rel_obj = NULL;

        // is the record already cached
        if($this->model->has_property($this->cache_name)):
            $rel_obj = $this->model->{$this->cache_name};
        else:
            // create queryset, fetch the record and cache the results

            $relations_model = $this->field->relation->get_model();
            $pk_field = $relations_model->meta->primary_key->name;

            $pk_value = $this->model->{$this->field->db_column};

            $rel_obj = $relations_model->one([$pk_field=>$pk_value]);

            // cache the result
            $this->model->{$this->cache_name} = $rel_obj;
        endif;

        if(!empty($name)):
            return $rel_obj->{$name};
        endif;

        return $rel_obj;
    }
}

class ReverseOneToOneAccessor extends Accessor{}

class ReverseManyToOneAccessor extends Accessor{}

class ForwardManyToManyAccessor extends ReverseManyToOneAccessor{}

