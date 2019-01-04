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

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Exception\NotImplemented;

class Recorder
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    //    private $schema;
    //    private $schemaManager;
    private $tableExist;

    private $migrationTableName = 'powerorm_migrations';

    /**
     * Recorder constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->createTable();
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return Recorder
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject(ConnectionInterface $connection)
    {
        return new static($connection);
    }

    public function getApplied()
    {
        $sql = sprintf('SELECT * FROM %s', $this->migrationTableName);
        $appliedMigrations = $this->connection
            ->fetchAll($sql);
        $applied = [];
        foreach ($appliedMigrations as $item) {
            $applied[$item['app']][$item['name']] = $item['name'];
        }

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
        if ($this->tableExist) {
            return;
        }
        $schemaM = $this->connection->getSchemaManager();
        $schema = $schemaM->createSchema();
        if (!$schemaM->tablesExist($this->migrationTableName)) {
            $myTable = $schema->createTable($this->migrationTableName);
            $myTable->addColumn(
                'id',
                'integer',
                ['unsigned' => true, 'autoincrement' => true]
            );
            $myTable->addColumn(
                'app',
                'string',
                ['length' => 254]
            );
            $myTable->addColumn(
                'name',
                'string',
                ['length' => 254]
            );
            $myTable->addColumn(
                'applied',
                'datetime',
                ['default' => 'CURRENT_TIMESTAMP']
            );
            $myTable->setPrimaryKey(['id']);

            $schemaM->createTable($myTable);
            $this->tableExist = true;
        }
    }
}
