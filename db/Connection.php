<?php
/**
 * Created by http://eddmash.com.
 * User: edd
 * Date: 5/26/16
 * Time: 1:54 PM
 */

namespace powerorm\db;

/**
 * Class Connection
 * @package powerorm\db
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Connection
{

    public $db;
    public $forge;

    public function __construct($db, $forge)
    {
        $this->db =$db;
        $this->forge =$forge;
    }

    public function __get($property)
    {
        if(property_exists($this->db, $property)):
            return $this->db->{$property};
        endif;

        if(property_exists($this->forge, $property)):
            return $this->forge->{$property};
        endif;
    }

    public function __call($method, $args)
    {
        if(method_exists($this->db, $method)):
            if(empty($args)):
                return call_user_func(array($this->db, $method));
            endif;
            return call_user_func_array([$this->db, $method], $args);
        endif;

        if(method_exists($this->forge, $method)):
            if(empty($args)):
                return call_user_func(array($this->forge, $method));
            endif;
            return call_user_func_array([$this->forge, $method], $args);
        endif;

    }

    public function orm_create_model($model)
    {
        foreach ($model->meta->local_fields as $field) :
            $this->orm_column_sql($field);
        endforeach;
    }


    /**
     * Converts a field to sql columns
     * @param $field
     * @return null
     */
    public function orm_column_sql($field)
    {
        $sql = $field->options($this);
    }

}