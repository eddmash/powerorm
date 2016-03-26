<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 2/24/16
 * Time: 11:28 AM
 */

namespace powerorm\db;


/**
 * Create Mysql related sql statements
 * @package powerorm\db
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MysqlStatements extends SqlStatements{
    public static $create_table = 'RunSql::create_table("%1$s", %2$s, %3$s);';
    public static $drop_table = 'RunSql::drop_table("%1$s", %2$s);';
    public static $add_field = 'RunSql::add_field("%1$s");';
    public static $add_column = 'RunSql::add_column("%1$s", "%2$s");';
    public static $drop_column = 'RunSql::drop_column("%1$s", "%2$s");';
    public static $modify_column = 'RunSql::modify_column("%1$s", %2$s);';
    public static $add_field_constraint = 'RunSql::add_field_constraint("%1$s");';
    public static $add_column_constraint = 'RunSql::add_column_constraint("%1$s", "%2$s");';
    public static $drop_constraint = 'RunSql::drop_constraint("%1$s", "%2$s");';
    public static $create_triggers = 'RunSql::create_trigger();';

    public static function _porm_create_table($name, $check_exist=TRUE, $attrs="['ENGINE'=>'InnoDB']"){

        if($check_exist===FALSE):
            $check_exist = "FALSE";
        else:
            $check_exist = "TRUE";
        endif;

        return [sprintf(MysqlStatements::$create_table, $name, $check_exist, $attrs)];
    }

    public static function _porm_drop_table($name, $check_exist=TRUE){

        if($check_exist===FALSE):
            $check_exist = "FALSE";
        else:
            $check_exist = "TRUE";
        endif;

        return [sprintf(MysqlStatements::$drop_table, $name, $check_exist)];
    }

    public static function add_table_field($fields){
        $field_sql = [];

        // normal field creation
        foreach ($fields as $field) :
            $field_sql[] = MysqlStatements::_string_add_field(implode(' ', MysqlStatements::to_db($field->options())));
        endforeach;

        // primary key
        if(MysqlStatements::pk_constraint($fields) != NULL):
            $field_sql[] = MysqlStatements::_string_add_field_constraint(MysqlStatements::pk_constraint($fields)) ;
        endif;

        // foreign key constraints
        if(MysqlStatements::fk_constraint($fields) != NULL):
            foreach (MysqlStatements::fk_constraint($fields) as $field) :
                $field_sql[] =  MysqlStatements::_string_add_field_constraint($field);
            endforeach;
        endif;

        // unique
        if(MysqlStatements::unique_constraint($fields) != NULL):
            foreach (MysqlStatements::unique_constraint($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_field_constraint($index);
            endforeach;

        endif;

        // index
        if(MysqlStatements::add_indexes($fields) != NULL):
            foreach (MysqlStatements::add_indexes($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_field_constraint($index);
            endforeach;

        endif;

        return $field_sql;
    }

    public static function alter_table_add_field($table, $fields){
        $field_sql = [];
        $many_to_many = [];

        // normal field creation
        foreach ($fields as $key=>$field) :
            $field_sql[] = MysqlStatements::_string_alter_add_field($table,
                implode(' ', MysqlStatements::to_db($field->options())));
        endforeach;

        // primary key
        if(MysqlStatements::pk_constraint($fields) != NULL):
            $field_sql[] = MysqlStatements::_string_add_column_constraint($table, MysqlStatements::pk_constraint($fields)) ;
        endif;

        // foreign key constraints
        if(MysqlStatements::fk_constraint($fields) != NULL):
            foreach (MysqlStatements::fk_constraint($fields) as $field) :
                $field_sql[] =  MysqlStatements::_string_add_column_constraint($table, $field);
            endforeach;
        endif;

        // unique
        if(MysqlStatements::unique_constraint($fields) != NULL):
            foreach (MysqlStatements::unique_constraint($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_column_constraint($table, $index);
            endforeach;

        endif;

        // index
        if(MysqlStatements::add_indexes($fields) != NULL):
            foreach (MysqlStatements::add_indexes($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_column_constraint($table, $index);
            endforeach;

        endif;

        return $field_sql;
    }

    public static function alter_table_field_modify($table, $fields){
        $field_sql = [];

        // normal field creation
        foreach ($fields as $field) :
            $field_sql[] = MysqlStatements::_string_custom_field_modify($table,
                implode(' ', MysqlStatements::to_db($field->options())));
        endforeach;

        // primary key
        if(MysqlStatements::pk_constraint($fields) != NULL):
            $field_sql[] = MysqlStatements::_string_custom_field_modify($table, MysqlStatements::pk_constraint($fields)) ;
        endif;

        // foreign key constraints
        if(MysqlStatements::fk_constraint($fields) != NULL):
            foreach (MysqlStatements::fk_constraint($fields) as $field) :
                $field_sql[] =  MysqlStatements::_string_custom_field_modify($table, $field);
            endforeach;
        endif;

        // unique
        if(MysqlStatements::unique_constraint($fields) != NULL):
            foreach (MysqlStatements::unique_constraint($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_custom_field_modify($table, $index);
            endforeach;

        endif;

        // index
        if(MysqlStatements::add_indexes($fields) != NULL):
            foreach (MysqlStatements::add_indexes($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_custom_field_modify($table, $index);
            endforeach;

        endif;

        return $field_sql;
    }

    public static function drop_table_field($table, $fields){
        $field_sql = [];

        $dbprefix = get_instance()->db->dbprefix;

        // drop foreignkey
        if(MysqlStatements::drop_fk_constraint($fields) != NULL):

            foreach (MysqlStatements::drop_fk_constraint($fields) as $index) :
                $field_sql[] = MysqlStatements::_string_drop_constraint($dbprefix.$table, $index);
            endforeach;
        endif;

        // drop indexes
        if(MysqlStatements::drop_indexes($fields) != NULL):
            foreach (MysqlStatements::drop_indexes($fields) as $index) :
                $field_sql[] = MysqlStatements::_string_drop_constraint($dbprefix.$table, $index);
            endforeach;
        endif;

        // normal field creation
        foreach ($fields as $field) :
            $field_sql[] = MysqlStatements::_string_drop_field($table, $field->db_column_name());
        endforeach;

        return $field_sql;
    }

    public static function default_value($field, $sql){

        if(isset($field['default']) && is_string($field['default']) && !empty($field['default'])):
            $sql['default']= sprintf("DEFAULT '%s'",  $field['default']);
        endif;

        if(isset($field['default']) && !is_string($field['default'])):
            $sql['default']= sprintf("DEFAULT %s",  $field['default']);
        endif;

        // default for boolean
        $sql = MysqlStatements::boolean_default($field, $sql);
        return $sql;
    }

    public static function boolean_default($field, $sql){
        if(!isset($field['type']) || strtolower($field['type']) != 'boolean'):
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

    /**
     * @param $fields
     * @return null|string
     */
    public static function pk_constraint($fields){
        foreach ($fields as $field) :
            $field = $field->options();

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
    public static function unique_constraint($fields){
        $unique = [];
        foreach ($fields as $field) :
            $field = $field->options();

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
    public static function fk_constraint($fields){
        $dbprefix = get_instance()->db->dbprefix;
        $action = [];
        foreach ($fields as $field) :
            $field = $field->options();

            // foreign key for both OneToOne or ManyToOne
            if((isset($field['O2O']) && $field['O2O'] == TRUE )||
                (isset($field['M2O']) && $field['M2O'] == TRUE)):

                $on_update = (empty($field['on_update']))? "CASCADE":strtoupper($field['on_update']);
                $on_delete = (empty($field['on_update']))? "CASCADE":strtoupper($field['on_delete']);

                $related_model = $field['related_model'];
                $related_model_table = $related_model->meta->db_table;
                $table_name = sprintf('%1$s%2$s', $dbprefix, strtolower($related_model_table));
                $related_pk = $related_model->meta->primary_key->name;

                $foreign_key = sprintf(
                    'CONSTRAINT %6$s
                    FOREIGN KEY (%1$s)
                    REFERENCES %2$s(%3$s)
                    ON UPDATE %4$s
                    ON DELETE %5$s',
                    $field['db_column'],
                    $table_name,
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

    public static function drop_fk_constraint($fields_collection){

        $action = '';
        if(empty($fields_collection)):
            return NULL;
        endif;

        foreach ($fields_collection as $field) :
            $field = $field->options();

            if((isset($field['O2O']) && $field['O2O'] == TRUE )||
                (isset($field['M2O']) && $field['M2O'] == TRUE)):
                $action[] = sprintf('FOREIGN KEY %1$s;', $field['constraint_name']);
            endif;
        endforeach;

        return $action;
    }

    public static function drop_indexes($fields){
        $action = [];

        // return NULL if there are not fields
        if(empty($fields)):
            return NULL;
        endif;

        // create and index statement for fields with db_index or unique set to true
        foreach ($fields as $field) :
            $field = $field->options();
            if($field['db_index'] || $field['unique']):
                $action[] = sprintf('INDEX %1$s',  $field['constraint_name']);
            endif;
        endforeach;

        return $action;
    }

    public static function add_indexes($fields){
        $indexes = [];
        foreach ($fields as $field) :
            $field = $field->options();
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

    public static function add_triggers($table, $fields){
        $dbprefix = get_instance()->db->dbprefix;
        $table = sprintf('%1$s%2$s', $dbprefix, strtolower($table));
        $date_fields_on_update = [];
        $date_fields_on_create = [];

        foreach ($fields as $field) :
            if($field->on_update ):
                $date_fields_on_update[] = $field->db_column;
            endif;

            if($field->on_update  || $field->on_creation):
                $date_fields_on_create[] = $field->db_column;
            endif;
        endforeach;

        $sql = [];
        
        if(!empty($date_fields_on_update)):

            $sql[] = sprintf('RunSql::create_trigger("%1$s", "%2$s", "%3$s", %4$s);',
                'BEFORE', 'UPDATE', $table, stringify($date_fields_on_update, 1, NULL, NULL, TRUE));

        endif;

        if(!empty($date_fields_on_create)):

            $sql[] = sprintf('RunSql::create_trigger("%1$s", "%2$s", "%3$s", %4$s);',
                'BEFORE', 'INSERT', $table, stringify($date_fields_on_create, 1, NULL, NULL, TRUE));

        endif;
        
        return $sql;
            
    }

    public static function drop_triggers($table, $fields){
        $dbprefix = get_instance()->db->dbprefix;
        $table = sprintf('%1$s%2$s', $dbprefix, strtolower($table));

        $date_fields_on_update = [];
        $date_fields_on_create = [];

        foreach ($fields as $field) :
            if($field->on_update ):
                $date_fields_on_update[] = $field->db_column;
            endif;

            if($field->on_update  || $field->on_creation):
                $date_fields_on_create[] = $field->db_column;
            endif;
        endforeach;

        $sql = [];

        if(!empty($date_fields_on_update)):

            $sql[] = sprintf('RunSql::drop_trigger("%1$s", "%2$s", "%3$s");',
                'BEFORE', 'UPDATE', $table);

        endif;

        if(!empty($date_fields_on_create)):

            $sql[] = sprintf('RunSql::drop_trigger("%1$s", "%2$s", "%3$s");',
                'BEFORE', 'INSERT', $table);

        endif;

        return $sql;

    }
}