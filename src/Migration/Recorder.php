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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\NotImplemented;

class Recorder
{
    /**
     * @var Connection
     */
    private $connection;
//    private $schema;
//    private $schemaManager;
    private $tableExist;
    private $migrationTableName = 'powerorm_migrations';

    /**
     * Recorder constructor.
     *
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->createTable();
    }

    /**
     * @param array $config
     *
     * @return Recorder
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($connection)
    {
        return new static($connection);
    }

    public function getApplied()
    {
        $appliedMigrations = $this->connection->fetchAll(sprintf('SELECT * FROM %s', $this->migrationTableName));
        $applied = [];
        foreach ($appliedMigrations as $item) :
            $applied[] = $item['name'];
        endforeach;

        return $applied;
    }

    public function recordApplied($data)
    {
        $this->connection->insert($this->migrationTableName, $data);
    }

    public function recordUnApplied($data)
    {
        $this->connection->delete($this->migrationTableName, $data);
    }

    public function flush()
    {
        throw new NotImplemented();
    }

    public function createTable()
    {
        if ($this->tableExist):

            return;
        endif;
        $schemaM = $this->connection->getSchemaManager();
        $schema = $schemaM->createSchema();
        if (!$schemaM->tablesExist($this->migrationTableName)):

            $myTable = $schema->createTable($this->migrationTableName);
        $myTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $myTable->addColumn('name', 'string', ['length' => 60]);
        $myTable->addColumn('applied', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $myTable->setPrimaryKey(['id']);

        $schemaM->createTable($myTable);
        $this->tableExist = true;
        endif;
    }
}
