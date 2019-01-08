<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\Model\Query;

use Eddmash\PowerOrm\Model\Query\Q;
use PHPUnit\Framework\TestCase;
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;
use const Eddmash\PowerOrm\Model\Query\Expression\OR_CONNECTOR;

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
