<?php
namespace powerorm\db;


/**
 * SQL statements to be used in creating migration files
 * @package powerorm\db
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface Statements
{
    public static function _porm_create_table($name, $check_exist, $attrs);
    public static function _porm_drop_table($table_name, $check_exist);
    public static function alter_table_add_field($table_name, $check_exist);

    public static function add_table_field($table_fields);
    public static function drop_table_field($table_name, $check_exist);

    public static function add_indexes($field);
    public static function drop_indexes($field);

    public static function add_triggers($table, $fields);
    public static function drop_triggers($table, $fields);


}

abstract class SqlStatements implements Statements{

    public static function to_db($field){
        // [NOT NULL | NULL] [DEFAULT default_value] [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
        $sql = [];

        if(isset($field['db_column'])):
            // use column name over field name because of the foreignkey relationships
            $sql['db_column'] = $field['db_column'];
        endif;

        if(isset($field['type'])):
            // datatype
            $sql['type'] = $field['type'];
        endif;

        if(isset($field['signed']) && !empty($field['signed'])):
            $sql['signed']= $field['signed'];
        endif;

        if(isset($field['null'])):
            $sql['null']= ($field['null'])? "NULL" :"NOT NULL";
        endif;

        if(isset($field['default'])):
            $sql = MysqlStatements::default_value($field, $sql);
        endif;


        // auto increment
        if(isset($field['auto']) && $field['auto']):
            $sql['auto']= 'AUTO_INCREMENT';
        endif;

        return $sql;
    }

    public static function _string_add_field($field){
        return sprintf(MysqlStatements::$add_field, $field);
    }

    public static function _string_add_field_constraint($field){
        return sprintf(MysqlStatements::$add_field_constraint, $field);
    }

    public static function _string_add_column_constraint($table, $field){
        return sprintf(MysqlStatements::$add_column_constraint, $table, $field);
    }

    public static function _string_alter_add_field($table, $field){
        return sprintf(MysqlStatements::$add_column, $table, $field);
    }

    public static function _string_modify_field($table, $field_options){

        if(!empty($field_options)):
            return sprintf(MysqlStatements::$modify_column, $table, stringify($field_options));
        endif;
    }

    public static function _string_drop_field($table, $field){
        return sprintf(MysqlStatements::$drop_column, $table, $field);
    }

    public static function _string_drop_constraint($table, $field){
        return sprintf(MysqlStatements::$drop_constraint, $table, $field);
    }

    public static function _string_custom_field_add($table, $field){
        return sprintf('$this->db->query("ALTER TABLE %1$s ADD %2$s");', $table, $field);
    }

    public static function _string_custom_field_modify($table, $field){
        return sprintf('$this->db->query("ALTER TABLE %1$s MODIFY %2$s");', $table, $field);
    }

}