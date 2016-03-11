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
    public static function create_table($name, $check_exist, $attrs);
    public static function add_table_field($table_fields);
    public static function drop_table($table_name, $check_exist);


}