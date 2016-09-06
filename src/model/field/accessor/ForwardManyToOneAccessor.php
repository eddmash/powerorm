<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/6/16
 * Time: 1:15 AM
 */

namespace eddmash\powerorm\model\field\accessor;

/**
 * Accessing from the many side to the one side
 *
 * Class ForwardManyToOneAccesor
 * @package eddmash\powerorm\model\field
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ForwardManyToOneAccessor extends Accessor
{
    public function __get($name)
    {
        return $this->_fetch_related_record($name);
    }

    public function __set($name, $value)
    {
    }

    public function __toString()
    {
        return (string)$this->_fetch_related_record();
    }

    public function _fetch_related_record($name = null)
    {
        $rel_obj = null;

        // is the record already cached
        if ($this->model->has_property($this->cache_name)):
            $rel_obj = $this->model->{$this->cache_name};
        else:
            // create queryset, fetch the record and cache the results

            $relations_model = $this->field->relation->get_model();
            $pk_field = $relations_model->meta->primary_key->name;

            $pk_value = $this->model->{$this->field->db_column};

            $rel_obj = $relations_model->one([$pk_field => $pk_value]);

            // cache the result
            $this->model->{$this->cache_name} = $rel_obj;
        endif;

        if (!empty($name)):
            return $rel_obj->{$name};
        endif;

        return $rel_obj;
    }
}
