<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/12/16
 * Time: 8:12 PM
 */

namespace powerorm\migrations\operations;


use powerorm\db\MysqlStatements;

class AddTriggers extends Operation{
    public $model_name;
    public $fields;
    public $options;

    public function __construct($name, $fields, $options){
        $this->model_name = $name;
        $this->fields = $fields;
        $this->options = $options;
    }

    public function up()
    {
        return MysqlStatements::add_triggers($this->db_table(), $this->fields);
    }

    public function down()
    {
        return MysqlStatements::drop_triggers($this->db_table(), $this->fields);
    }

    public function message()
    {
        return 'add_triggers';
    }

    public function state(){
        $model = ['model_name'=>strtolower($this->model_name),'operation'=>'add_triggers'];
        $model = array_merge($model, $this->options);

        $fields['fields'] = [];
        foreach ($this->fields as $field_name=>$field_obj) :
            $fields['fields'][$field_name] = $field_obj->skeleton();
        endforeach ;

        return array_merge($model, $fields);
    }

}
class DropTriggers extends Operation{
    public $model_name;
    public $fields;
    public $options;

    public function __construct($name, $fields, $options){
        $this->model_name = $name;
        $this->fields = $fields;
        $this->options = $options;
    }

    public function up()
    {
        return MysqlStatements::drop_triggers($this->db_table(), $this->fields);
    }

    public function down()
    {
        return MysqlStatements::add_triggers($this->db_table(), $this->fields);
    }

    public function message()
    {
        return 'drop_triggers';
    }

    public function state(){
        $model = ['model_name'=>strtolower($this->model_name),'operation'=>'drop_triggers'];
        $model = array_merge($model, $this->options);

        $fields['fields'] = [];
        foreach ($this->fields as $field_name=>$field_obj) :
            $fields['fields'][$field_name] = $field_obj->skeleton();
        endforeach ;

        return array_merge($model, $fields);
    }

}