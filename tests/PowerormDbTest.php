<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 10/4/18
 * Time: 9:05 AM.
 */

namespace Eddmash\PowerOrm\Tests;

use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Tests\TestApp\Test;
use PHPUnit\DbUnit\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class PowerormDbTest extends TestCase
{
    use TestCaseTrait;

    // only instantiate pdo once for test clean-up/fixture load
    private static $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;


    protected function setUp(): void
    {
        BaseOrm::setup(
            new Settings(
                [
                    'database' => [
                        'host' => '127.0.0.1',
                        'dbname' => $GLOBALS['DB_DBNAME'],
                        'user' => $GLOBALS['DB_USER'],
                        'password' => $GLOBALS['DB_PASSWD'],
                        'driver' => 'pdo_mysql',
                    ],
                    'components' => [
                        Test::class,
                    ],
                ]
            )
        );
    }

    /**
     * @return null|\PHPUnit\DbUnit\Database\DefaultConnection
     */
    final public function getConnection()
    {
        if (null === $this->conn) {
            if (null == self::$pdo) {
                self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'],
                    $GLOBALS['DB_PASSWD']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }
        return $this->conn;
    }

    /**
     * @return \PHPUnit\DbUnit\DataSet\ArrayDataSet
     */
    public function getDataSet()
    {
        return $this->createArrayDataSet([]);
    }

    private function queryAsSql(Query $query)
    {
        $compiler = $query->getSqlCompiler($this->getDbConnection());
        $compiler->quotable = false;
        return $compiler->asSql();
    }

    public function assertQuery($query, $expected)
    {
        list($sql, $params) = $this->queryAsSql($query);

        $this->assertEquals(trim($sql), $expected[0]);
        $this->assertEquals($params, $expected[1]);
    }

    public function getDbConnection()
    {
        return BaseOrm::getDbConnection();
    }
}
