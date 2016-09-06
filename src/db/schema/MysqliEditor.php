<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 5/28/16
 * Time: 8:59 AM
 */

namespace eddmash\powerorm\db\schema;

use eddmash\powerorm\model\field\Field;

/**
 * Class MysqliEditor
 * @package eddmash\powerorm\db\schema
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MysqliEditor extends BaseEditor implements SchemaEditorInterface
{
    public $sql_delete_fk = 'ALTER TABLE %1$s DROP FOREIGN KEY  %2$s';
    public $sql_delete_unique = 'ALTER TABLE %1$s DROP INDEX %2$s';
    public $sql_delete_index = 'DROP INDEX %2$s ON %1$s';

    public function create_table($table, $if_not_exists = false, array $attributes = array())
    {
        parent::create_table($table, $if_not_exists, ['ENGINE' => 'InnoDB']);
    }

    public function skip_default(Field $field)
    {
        $db_type = $field->db_type($this->get_connection());
        $columns = ['tinyblob', 'blob', 'mediumblob', 'longblob', 'tinytext', 'text', 'mediumtext', 'longtext'];

        return (!$db_type && in_array(strtolower($db_type), $columns));
    }
}
