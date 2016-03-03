<?php
namespace powerorm\db;

interface Statements
{
    public static function create_table($name, $check_exist, $attrs);
    public static function add_table_field($table_fields);
    public static function drop_table($table_name, $check_exist);


}