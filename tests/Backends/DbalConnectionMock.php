<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\Backends;

use Doctrine\DBAL\Connection;

class DbalConnectionMock extends Connection
{
    /** @var DatabasePlatformMock */
    private $platformMock;

    /** @var int */
    private $lastInsertId = 0;

    /** @var string[][] */
    private $inserts = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        $this->platformMock = new DatabasePlatformMock();
        parent::__construct($params, $driver, $config, $eventManager);
    }

    public function getDatabasePlatform()
    {
        return new DatabasePlatformMock();
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $data, array $types = [])
    {
        $this->inserts[$tableName][] = $data;
    }

    public function lastInsertId($seqName = null)
    {
        return $this->lastInsertId;
    }

    public function quote($input, $type = null)
    {
        if (is_string($input)) {
            return "'" . $input . "'";
        }
        return $input;
    }

    public function setLastInsertId($id)
    {
        $this->lastInsertId = $id;
    }

    public function getInserts()
    {
        return $this->inserts;
    }

    public function reset()
    {
        $this->inserts = [];
        $this->lastInsertId = 0;
    }

    public function quoteIdentifier($str)
    {
        return $str;
    }
}
