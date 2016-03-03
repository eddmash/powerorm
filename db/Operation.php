<?php
namespace powerorm\db;

use powerorm\migrations\MysqlStatements;
use powerorm\migrations\RunSql;

abstract class Operation{
    public $table_name;
    public $op_type;

    public function __construct($table){
        $this->table_name = $table;
        $this->op_type = $this->op_type();
    }
    public abstract function up();
    public abstract function down();

    /**
     * Sets the type of operation current class does
     * @return mixed
     */
    public abstract function op_type();


    /**
     * @param $fields
     * @return null|string
     */
    public function pk_constraint($fields){
        foreach ($fields as $field) :

            // primary key
            if(isset($field['primary_key']) && $field['primary_key']):
                return sprintf('PRIMARY KEY (%s)', $field['db_column']);
            endif;

        endforeach;

        return NULL;
    }

    /**
     * @param $fields
     * @return null|string
     */
    public function unique_constraint($fields){
        $unique = [];
        foreach ($fields as $field) :
            // unique key
            if(isset($field['unique']) && $field['unique']):
//                $unique[] = $field['db_column'];
                $unique[]=sprintf('UNIQUE KEY %1$s (%2$s)', $field['constraint_name'], $field['db_column']);
            endif;
        endforeach;

        if(!empty($unique)):
            return $unique;
        endif;

        return NULL;
    }

    /**
     * @param $fields
     * @param bool|TRUE $alter
     * @return array|null
     */
    public function fk_constraint($fields){

        $action = [];
        foreach ($fields as $field) :

            // foreign key for both OneToOne or ManyToOne
            if((isset($field['O2O']) && $field['O2O'] == TRUE )||
                (isset($field['M2O']) && $field['M2O'] == TRUE)):

                $on_update = (empty($field['on_update']))? "CASCADE":strtoupper($field['on_update']);
                $on_delete = (empty($field['on_update']))? "CASCADE":strtoupper($field['on_delete']);

                $related_model = $field['related_model'];
                $related_model_table = $related_model->meta->db_table;
                $related_pk = $related_model->meta->primary_key->name;

                $foreign_key = sprintf(
                    'CONSTRAINT %6$s
                    FOREIGN KEY (%1$s)
                    REFERENCES %2$s(%3$s)
                    ON UPDATE %4$s
                    ON DELETE %5$s',
                    $field['db_column'],
                    strtolower($related_model_table),
                    $related_pk,
                    $on_update,
                    $on_delete,
                    $field['constraint_name']
                );

                $action[]=$foreign_key;
            endif;
        endforeach;

        if(!empty($action)):
            return $action;
        endif;

        return NULL;
    }

    public function drop_fk_constraint($fields_collection){
        $action = '';
        if(empty($fields_collection)):
            return NULL;
        endif;
        $table = $this->table_name;
        foreach ($fields_collection as $field) :
            if((isset($field['O2O']) && $field['O2O'] == TRUE )||
                (isset($field['M2O']) && $field['M2O'] == TRUE)):
                $action[] = sprintf('FOREIGN KEY %1$s;', $field['constraint_name']);
            endif;
        endforeach;

        return $action;
    }

    public function drop_indexes($fields){
        $action = [];

        // return NULL if there are not fields
        if(empty($fields)):
            return NULL;
        endif;

        // create and index statement for fields with db_index or unique set to true
        foreach ($fields as $field) :
            if($field['db_index'] || $field['unique']):
                $action[] = sprintf('INDEX %1$s',  $field['constraint_name']);
            endif;
        endforeach;

        return $action;
    }

    public function add_indexes($fields){
        $indexes = [];
        foreach ($fields as $field) :
            // index key
            if(isset($field['db_index']) && $field['db_index']):

                $indexes[]=sprintf('INDEX %1$s (%2$s)', $field['constraint_name'], $field['db_column']);
            endif;
        endforeach;

        if(!empty($indexes)):
            return $indexes;
        endif;

        return NULL;
    }

    public function db_field($field){
        // [NOT NULL | NULL] [DEFAULT default_value] [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
        $sql = [];

        // use column name over field name because of the foreignkey relationships
        $sql['db_column'] = $field['db_column'];

        // datatype
        $sql['type'] = $field['type'];

        $sql['signed']= $field['signed'];

        $sql['null']=$field['null'];

        $sql = $this->default_value($field, $sql);

        // auto increment
        if($field['auto']):
            $sql['auto']= 'AUTO_INCREMENT';
        endif;

        return $sql;
    }

    public function default_value($field, $sql){

        if(isset($field['default']) && is_string($field['default']) && !empty($field['default'])):
            $sql['default']= sprintf("DEFAULT '%s'",  $field['default']);
        endif;

        if(isset($field['default']) && !is_string($field['default'])):
            $sql['default']= sprintf("DEFAULT %s",  $field['default']);
        endif;

        // default for boolean
        $sql = $this->boolean_default($field, $sql);
        return $sql;
    }

    public function boolean_default($field, $sql){
        if(strtolower($field['type']) != 'boolean'):
            return $sql;
        endif;

        if($field['default'] === FALSE):
            $field['default'] = "FALSE";
        endif;

        if($field['default'] === TRUE):
            $field['default'] = "TRUE";
        endif;

        $sql['default']= sprintf("DEFAULT %s",  $field['default']);

        return $sql;
    }

    public function table_action($action, $field){
        $lib = 'dbforge';
        if($action==='query'):
            $lib='db';
        endif;

        return "\t\t".sprintf('$this->%3$s->%1$s("%2$s");', $action, $field, $lib).PHP_EOL;
    }

    public function table_create($field, $test, $attrs){
        return "\t\t".sprintf('$this->dbforge->create_table("%1$s", %2$s, %3$s);', $field, $test, $attrs).PHP_EOL;
    }

    public function alter_table_action($table, $action, $field){
        $lib = 'dbforge';
        if($action==='query'):
            $lib='db';
        endif;

        return "\t\t".sprintf('$this->%1$s->%2$s("%3$s", "%4$s");', $lib, $action, $table, $field).PHP_EOL;
    }

    public function query($sql){

        return "\t\t".sprintf('$this->db->query("%s");', $sql).PHP_EOL;
    }
}

class MysqlCreateOperation extends Operation{

    public $past;
    public $now;

    public function __construct($table, $now, $past=NULL){
        parent::__construct($table);
        $this->past = $past;
        $this->now = $now;
    }

    public function up()
    {
        $all_fields = $this->now['fields'];
        $action = '';

        // *********************constraint order
        // a PRIMARY KEY is placed first,
        // followed by all UNIQUE indexes,
        // and then the nonunique indexes.
        // **********************************
        if($this->pk_constraint($all_fields) != NULL):
            $action .=  $this->table_action('add_field', $this->pk_constraint($all_fields));
        endif;

        if($this->unique_constraint($all_fields) != NULL):
            foreach ($this->unique_constraint($all_fields) as $index) :
                $action .=  $this->table_action('add_field', $index);
            endforeach;

        endif;

        if($this->fk_constraint($all_fields) != NULL):
            foreach ($this->fk_constraint($all_fields) as $field) :
                $action .=  $this->table_action('add_field', $field);
            endforeach;
        endif;

        if($this->add_indexes($all_fields) != NULL):
            foreach ($this->add_indexes($all_fields) as $index) :
                $action .= $this->table_action('add_field', $index);
            endforeach;

        endif;

        $action .= $this->table_create($this->table_name, 'TRUE', "['ENGINE'=>'InnoDB']");

        return $action;
    }

    public function down(){

        return $this->table_action('drop_table', $this->table_name);
    }

    public function op_type(){
        return 'create';
    }

}

class MysqlDropOperation extends MysqlCreateOperation{

    public function __construct($table, $now=NULL, $past=NULL){
        parent::__construct($table, $now, $past);
    }

    public function up()
    {
        return $this->table_action('drop_table', $this->table_name);
    }

    public function down()
    {
        return '';
    }

    public function op_type()
    {
        return 'drop';
    }

}

class MysqlAlterAddOperation extends Operation{
    public $add_fields;
    public function __construct($table, $add_fields){
        parent::__construct($table);
        $this->add_fields = $add_fields;
    }

    public function up()
    {
        $action = '';
        if(!empty($this->add_fields)):

            foreach ($this->add_fields as $field) :

                $field_string = implode(" ", $this->db_field($field));

                $action .= $this->alter_table_action($this->table_name, 'add_column', $field_string);

            endforeach;


            if($this->unique_constraint($this->add_fields) != NULL):
                foreach ($this->unique_constraint($this->add_fields) as $index) :
                    $action .=  $this->table_action('add_field', $index);
                endforeach;

            endif;


            if($this->fk_constraint($this->add_fields) != NULL):
                foreach ($this->fk_constraint($this->add_fields) as $fk_constraint) :
                    $sql = "ALTER TABLE $this->table_name ADD $fk_constraint";
                    $action .=  $this->table_action('query', $sql);
                endforeach;
            endif;

        endif;


        return $action;
    }

    public function down()
    {
        $action = '';
        // if any field were added in up() reverse action is to drop them
        // dropping column drops its indexes
        if(!empty($this->add_fields)):

            if($this->drop_fk_constraint($this->add_fields) != NULL):
                foreach ($this->drop_fk_constraint($this->add_fields) as $fk_constraint) :
                    $sql = "ALTER TABLE $this->table_name DROP $fk_constraint";
                    $action .=  $this->table_action('query', $sql);
                endforeach;
            endif;
//            $action .= $this->drop_fk_constraint($this->add_fields);

            foreach ($this->add_fields as $field) :
                $action .= $this->alter_table_action($this->table_name, 'drop_column', $field['db_column']);
            endforeach;

        endif;
        return $action;
    }

    public function op_type()
    {
        return  'alter_add';
    }
}

class MysqlAlterDropOperation extends Operation{
    public $drop_fields;

    public function __construct($table, $drop_fields){
        parent::__construct($table);
        $this->drop_fields = $drop_fields;
    }

    public function up()
    {
        $action = '';

        if($this->drop_fk_constraint($this->drop_fields) != NULL):
            foreach ($this->drop_fk_constraint($this->drop_fields) as $fk_constraint) :
                $sql = "ALTER TABLE $this->table_name DROP $fk_constraint";
                $action .=  $this->table_action('query', $sql);
            endforeach;
        endif;

        if(!empty($this->drop_fields)):
            foreach ($this->drop_fields as $field) :
                $action .= $this->alter_table_action($this->table_name, 'drop_column', $field['db_column']);

            endforeach;
        endif;

        return $action;
    }

    public function down()
    {
        $action = '';

        if(!empty($this->drop_fields)):
            foreach ($this->drop_fields as $field) :
                $field_string = implode(" ", $this->db_field($field));
                $action .= $this->alter_table_action($this->table_name, 'add_column', $field_string);
            endforeach;
        endif;

//        var_dump($this->drop_fields);
        if($this->fk_constraint($this->drop_fields) != NULL):
            foreach ($this->fk_constraint($this->drop_fields) as $fk_constraint) :
                $sql = "ALTER TABLE $this->table_name ADD $fk_constraint";
                $action .=  $this->table_action('query', $sql);
            endforeach;
        endif;
       return $action;
    }

    public function op_type()
    {
        return 'alter_drop';
    }
}

class MysqlModifyOperation extends Operation{
    public $modified_fields;
    public $uniques_fields=[];
    public $index_fields=[];
    public $index_drop=[];

    public function __construct($table, $modified_fields){
        parent::__construct($table);
        $this->modified_fields = $modified_fields;
    }

    public function up()
    {
        $action = '';

        // normal renaming
        // normal change like type
        if(!empty($this->modified_fields)):

            foreach ($this->modified_fields as $modified_field) :
                $present = $modified_field['present'];
                $past = $modified_field['past'];

                // look at the modifications
                $modification = array_diff_assoc($present, $past);

                $sql = 'ALTER TABLE '.$this->table_name;

                $action_ = 'MODIFY';
                // look for name change
                if($present['db_column']!=$past['db_column']):
                    $action_ ='CHANGE';
                endif;

                // we are not using the equality operators, we want '', FALSE to mean the same thing
                if(($present['unique']!=$past['unique'])):

                    if($present['unique']):
                        $this->uniques_fields[] = $present;
                    else:
                        // this is a drop of the constraint
                        $this->index_drop[] = $past;
                    endif;
                endif;

                if(($present['db_index']!=$past['db_index'])):
                    if($present['db_index']):
                        $this->index_fields[] = $present;
                    else:
                        $this->index_drop[] = $past;
                    endif;
                endif;

                // if the modification was only on indexes just stop don't continue
                if(count($modification)==2 &&
                    array_key_exists('unique', $modification) &&
                    array_key_exists('constraint_name', $modification)):
                    continue;
                endif;

                if(count($modification)==2 &&
                    array_key_exists('db_index', $modification) &&
                    array_key_exists('constraint_name', $modification)):
                    continue;
                endif;

                if(!empty($modification)):
                    $sql .=' '.$action_.' '.implode(' ', $this->db_field($present));

                    $action .= $this->query($sql);
                endif;

            endforeach;

            if(!empty($this->uniques_fields)):

                foreach ($this->unique_constraint($this->uniques_fields) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s ADD %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;

            endif;

            if(!empty($this->index_fields)):
                foreach ($this->add_indexes($this->index_fields) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s ADD %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;
            endif;

            // drop constraints
            if(!empty($this->index_drop)):
                foreach ($this->drop_indexes($this->index_drop) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s DROP %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;

            endif;

            return $action;
        endif;

        return $action;

    }

    public function down()
    {
        $action = '';

        if(!empty($this->modified_fields)):

            foreach ($this->modified_fields as $modified_field) :
                $present = $modified_field['present'];
                $past = $modified_field['past'];

                $sql = 'ALTER TABLE '.$this->table_name;

                $action_ = 'MODIFY';
                // look for name change
                if($present['db_column']!==$past['db_column']):
                    $action_ ='CHANGE';
                endif;

                $modification = array_diff_assoc($present, $past);

                if(count($modification)==2 &&
                    array_key_exists('unique', $modification) &&
                    array_key_exists('constraint_name', $modification)):
                    continue;
                endif;

                if(count($modification)==2 &&
                    array_key_exists('db_index', $modification) &&
                    array_key_exists('constraint_name', $modification)):
                    continue;
                endif;

                if(!empty($modification)):
                    $sql .=' '.$action_.' '.implode(' ', $this->db_field($past));

                    $action .= $this->query($sql);
                endif;

            endforeach;

            if(!empty($this->uniques_fields)):

                foreach ($this->drop_indexes($this->uniques_fields) as $index) :

                    $sql = sprintf('ALTER TABLE %1$s DROP %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;
            endif;

            if(!empty($this->index_fields)):
                foreach ($this->drop_indexes($this->index_fields) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s DROP %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;
            endif;
        endif;

        if(!empty($this->index_drop)):

            if($this->add_indexes($this->index_drop) !=NULL):

                foreach ($this->add_indexes($this->index_drop) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s ADD %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;
            endif;

            if($this->unique_constraint($this->index_drop) !=NULL):

                foreach ($this->unique_constraint($this->index_drop) as $index) :
                    $sql = sprintf('ALTER TABLE %1$s ADD %2$s',
                        $this->table_name,
                        $index);

                    $action .= $this->query($sql);
                endforeach;
            endif;


        endif;



        return $action;
    }

    public function op_type()
    {
        return 'modify';
    }

}