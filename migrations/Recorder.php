<?php
/**
 * Created by http://eddmash.com.
 * User: edd
 * Date: 5/26/16
 * Time: 1:03 PM.
 */
namespace powerorm\migrations;

use powerorm\console\Base;

/**
 * Class Recorder.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Recorder extends Base
{
    public $dbconnection;
    public $migration_table_name = 'orm_migrations';

    public function __construct($dbconnection)
    {
        $this->dbconnection = &$dbconnection;
    }

    public function get_applied()
    {
        $this->create_table();
        $applied_migrations = $this->dbconnection->get($this->migration_table_name)->result();

        $applied = [];
        foreach ($applied_migrations as $item) :
            $applied[] = $item->name;
        endforeach;

        return $applied;
    }

    public function record_applied($data)
    {
        $this->create_table();

        $this->dbconnection->insert($this->migration_table_name, $data);
    }

    public function record_unapplied($data)
    {
        $this->create_table();

        $this->dbconnection->delete($this->migration_table_name, $data);
    }

    public function flush()
    {
        $this->create_table();
        $this->dbconnection->empty_table($this->migration_table_name);
    }

    public function create_table()
    {
        if ($this->dbconnection->table_exists($this->migration_table_name)):
           return;
        endif;

        $fields = [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'TEXT',
            ],
        ];

        $this->dbconnection->schema_editor->add_field($fields);
        $this->dbconnection->schema_editor->add_key('id', true);
        $this->dbconnection->schema_editor->create_table($this->migration_table_name, true);
//        if (!)
//        {
//            $this->error("Migration Table could not created, ensure you database is setup correctly");
//        }
    }
}
