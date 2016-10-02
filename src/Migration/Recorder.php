<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

class Recorder
{
    public $connection;
    public $migration_table_name = 'orm_migrations';

    public function __construct($connection)
    {
        $this->connection = $connection;

    }

    public function getApplied()
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
        $this->createTable();
        $this->connection->emptyTable($this->migration_table_name);
    }

    public function createTable()
    {
        if ($this->connection->tableExists($this->migration_table_name)):
            return;
        endif;

        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'name' => array(
                'type' => 'TEXT',
            ),
        );

        $this->connection->add_field($fields);
        $this->connection->add_key('id', true);
        if (!$this->connection->create_table($this->migration_table_name, true)) {
            $this->error('Migration Table could not created, ensure you database is setup correctly');
        }

    }
}
