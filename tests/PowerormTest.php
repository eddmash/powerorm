<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests;

use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Model\Query\Queryset;
use Eddmash\PowerOrm\Tests\Backends\ConnectionMock;
use Eddmash\PowerOrm\Tests\Backends\DatabasePlatformMock;
use Eddmash\PowerOrm\Tests\TestApp\TestApp;
use Eddmash\PowerOrm\Tests\TestingApps\AutodetectorTest\AutodetectorTestApp;
use PHPUnit\Framework\TestCase;

define('BASEPATH', dirname(dirname(__FILE__)));

abstract class PowerormTest extends TestCase
{
    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    protected function setUp(): void
    {
        $this->conn = $this->getMockBuilder(ConnectionMock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->conn->method('getDatabasePlatform')
            ->willReturn(new DatabasePlatformMock());
        $this->conn->expects($this->any())->method('quoteIdentifier')
            ->will($this->returnArgument(0));
//        BaseOrm::setup(
//            new Settings(
//                [
//                    'components' => [
//                        TestApp::class,
//                        AutodetectorTestApp::class
//                    ],
//                ]
//            ),
//            $this->conn
//        );
    }

    public function getNewOrm(Settings $settings)
    {
        $instance = new BaseOrm($settings);
        $instance->load($this->conn);
        return $instance;
    }

    private function queryAsSql(Query $query)
    {
        $compiler = $query->getSqlCompiler($this->conn);
        return $compiler->asSql();
    }

    public function assertQuery($query, $expected)
    {
        if ($query instanceof Queryset) {
            $query = $query->query;
        }

        if ($query instanceof Query) {
            list($sql, $params) = $this->queryAsSql($query);
        }

        $this->assertEquals($expected[0], trim($sql));
        $this->assertEquals($expected[1], $params);
    }
}
