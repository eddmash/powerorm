<?php
namespace powerorm\migrations\operations;


use powerorm\db\MysqlStatements;

/**
 * Class AddModel
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AddModel extends Operation{
    public $model_name;
    public $fields;
    public $options;

    public function __construct($name, $fields, $options){
        $this->model_name = $name;
        $this->fields = $fields;
        $this->options = $options;
    }

    public function up(){

        $table = MysqlStatements::create_table($this->db_table());
        $fields = MysqlStatements::add_table_field($this->fields);
        $triggers =  MysqlStatements::date_fields_triggers($this->db_table(), $this->fields);
        return array_merge($fields, $table, $triggers);

    }

    public function down()
    {
        return array_merge(MysqlStatements::drop_table($this->db_table()),
            MysqlStatements::date_fields_drop_triggers($this->db_table(), $this->fields));
    }

    public function message()
    {
        return "create";
    }

    public function state(){
        $model = ['model_name'=>$this->model_name,'operation'=>'add_model'];
        $model = array_merge($model, $this->options);

        $fields['fields'] = [];
        foreach ($this->fields as $field_name=>$field_obj) :
            $fields['fields'][$field_name] = $field_obj->skeleton();
        endforeach ;

        return array_merge($model, $fields);
    }

}

/**
 * Class DropModel
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DropModel extends Operation
{
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
        return array_merge(MysqlStatements::drop_table($this->db_table()),
            MysqlStatements::date_fields_drop_triggers($this->db_table(), $this->fields));
    }

    public function down()
    {
        $table = MysqlStatements::create_table($this->db_table());
        $fields = MysqlStatements::add_table_field($this->fields);
        $triggers =  MysqlStatements::date_fields_triggers($this->db_table(), $this->fields);
        return array_merge($fields, $table, $triggers);
    }

    public function message()
    {
        return 'drop_table';
    }
    
    public function state(){
        $model = ['model_name'=>$this->model_name,'operation'=>'drop_model'];
        $model = array_merge($model, $this->options);

        $fields['fields'] = [];
        foreach ($this->fields as $field_name=>$field_obj) :
            $fields['fields'][$field_name] = $field_obj->skeleton();
        endforeach ;

        return array_merge($model, $fields);
    }

}