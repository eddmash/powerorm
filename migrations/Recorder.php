<?php
/**
 * Created by http://eddmash.com.
 * User: edd
 * Date: 5/26/16
 * Time: 1:03 PM
 */

namespace powerorm\migrations;

use powerorm\console\Base;

/**
 * Class Recorder
 * @package powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Recorder extends Base
{
    public $connection;
    public $migration_table_name = "orm_migrations";

    public function __construct($connection)
    {
        $this->connection = $connection;

    }
    
    public function get_applied()
    {
        $this->create_table();
        $applied_migrations = $this->connection->get($this->migration_table_name)->result();

        $applied = [];
        foreach ($applied_migrations as $item) :
            $applied[] = $item->name;
        endforeach;

        return $applied;
    }

    public function record_applied($data)
    {
        $this->create_table();

        $this->connection->insert($this->migration_table_name, $data);

    }

    public function record_unapplied($data)
    {
        $this->create_table();

        $this->connection->delete($this->migration_table_name, $data);
    }

    public function flush()
    {
        $this->create_table();
        $this->connection->empty_table($this->migration_table_name);
    }

    public function create_table()
    {
        if($this->connection->table_exists($this->migration_table_name)):
           return;
        endif;

        $fields = array(
            'id' =>array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'TEXT',
            )
        );

        $this->connection->add_field($fields);
        $this->connection->add_key('id', TRUE);
        if (!$this->connection->create_table($this->migration_table_name, TRUE))
        {
            $this->error("Migration Table could not created, ensure you database is setup correctly");
        }


    }
}