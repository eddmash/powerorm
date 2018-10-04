<?php

namespace Eddmash\PowerOrm\Tests\Model\Query;

use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;
use const Eddmash\PowerOrm\Model\Query\Expression\OR_CONNECTOR;
use Eddmash\PowerOrm\Model\Query\Q;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: edd
 * Date: 10/4/18
 * Time: 7:53 AM.
 */
class QTest extends TestCase
{
    public function testQcombineUsingAnd()
    {
        $q1 = new Q(['name' => 'jane']);
        $q2 = new Q(['age' => 50]);

        $this->assertEquals($q1->and_($q2)->getConnector(),
            AND_CONNECTOR);
    }

    public function testQcombineUsingOr()
    {
        $q1 = new Q(['name' => 'jane']);
        $q2 = new Q(['age' => 50]);

        $this->assertEquals($q1->or_($q2)->getConnector(),
            OR_CONNECTOR);
    }
}
