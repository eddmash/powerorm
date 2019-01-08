<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\Migration;

use Eddmash\PowerOrm\Exception\CircularDependencyError;
use Eddmash\PowerOrm\Migration\Graph;
use Eddmash\PowerOrm\Migration\Migration;
use Eddmash\PowerOrm\Tests\PowerormTest;

class GraphTest extends PowerormTest
{
    /**
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     */
    public function testLinearGraph()
    {
        $app1M1 = new Migration("app1\m1");
        $app1M1->setAppLabel('app1');

        $app1M2 = new Migration("app1\m2");
        $app1M2->setAppLabel('app1');
        $app1M2->setDependency('app1', "app1\m1");

        $app2M1 = new Migration("app2\m1");
        $app2M1->setAppLabel('app2');
        $app2M1->setDependency('app1', "app1\m2");

        $app2M2 = new Migration("app2\m2");
        $app2M2->setAppLabel('app2');
        $app2M2->setDependency('app2', "app2\m1");

        $graph = new Graph();
        $graph->addNode("app1\m1", $app1M1);
        $graph->addNode("app1\m2", $app1M2);
        $graph->addNode("app2\m1", $app2M1);
        $graph->addNode("app2\m2", $app2M2);

        $graph->addDependency("app1\m2", $app1M2->getDependency(), $app1M2);
        $graph->addDependency("app2\m1", $app2M1->getDependency(), $app2M1);
        $graph->addDependency("app2\m2", $app2M2->getDependency(), $app2M2);

        //get root
        $this->assertEquals(
            ['app1' => "app1\m1"],
            $graph->getRootNodes('app2', "app2\m2")
        );

        // get Leader
        $this->assertEquals(["app2\m2"], $graph->getLeafNodes('app2'));
        $this->assertEquals(["app1\m2"], $graph->getLeafNodes('app1'));

        // get forward migration plan
        $this->assertEquals(
            [
                "app2\m2",
                "app2\m1",
                "app1\m2",
                "app1\m1",
            ],
            array_keys($graph->getDecedentsTree('app1', "app1\m1"))
        );
        // get reverse migration plan
        $this->assertEquals(
            [
                "app1\m1",
                "app1\m2",
                "app2\m1",
                "app2\m2",
            ],
            array_keys($graph->getAncestryTree('app2', "app2\m2"))
        );
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     */
    public function testBranchedGraph()
    {
        $app1M1 = new Migration("app1\m1");
        $app1M1->setAppLabel('app1');

        $app1M2 = new Migration("app1\m2");
        $app1M2->setAppLabel('app1');
        $app1M2->setDependency('app1', "app1\m1");

        $app2M1 = new Migration("app2\m1");
        $app2M1->setAppLabel('app2');
        $app2M1->setDependency('app1', "app1\m2");

        $app2M2 = new Migration("app2\m2");
        $app2M2->setAppLabel('app2');
        $app2M2->setDependency('app2', "app2\m1");

        $app2M3 = new Migration("app2\m3");
        $app2M3->setAppLabel('app2');
        $app2M3->setDependency('app1', "app1\m2");
        $app2M3->setDependency('app2', "app2\m2");

        $graph = new Graph();
        $graph->addNode("app1\m1", $app1M1);
        $graph->addNode("app1\m2", $app1M2);
        $graph->addNode("app2\m1", $app2M1);
        $graph->addNode("app2\m2", $app2M2);
        $graph->addNode("app2\m3", $app2M3);

        $graph->addDependency("app1\m2", $app1M2->getDependency(), $app1M2);
        $graph->addDependency("app2\m1", $app2M1->getDependency(), $app2M1);
        $graph->addDependency("app2\m2", $app2M2->getDependency(), $app2M2);

        foreach ($app2M3->getDependency() as $app => $mig) {
            $graph->addDependency("app2\m3", [$app => $mig], $app2M3);
        }

        //get root
        $this->assertEquals(
            ['app1' => "app1\m1"],
            $graph->getRootNodes('app2', "app2\m3")
        );

        // get Leaf
        $this->assertEquals(["app2\m3"], $graph->getLeafNodes('app2'));
        $this->assertEquals(["app1\m2"], $graph->getLeafNodes('app1'));

        // get forward migration plan
        $this->assertEquals(
            [
                "app2\m3",
                "app2\m2",
                "app2\m1",
                "app1\m2",
                "app1\m1",
            ],
            array_keys($graph->getDecedentsTree('app1', "app1\m1"))
        );
        // get reverse migration plan
        $this->assertEquals(
            [
                "app1\m1",
                "app1\m2",
                "app2\m1",
                "app2\m2",
                "app2\m3",
            ],
            array_keys($graph->getAncestryTree('app2', "app2\m3"))
        );
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     */
    public function testCircularGraphInForwardPlan()
    {
        $app1M1 = new Migration("app1\m1");
        $app1M1->setAppLabel('app1');
        $app1M1->setDependency('app2', "app2\m1");

        $app1M2 = new Migration("app1\m2");
        $app1M2->setAppLabel('app1');
        $app1M2->setDependency('app1', "app1\m1");

        $app2M1 = new Migration("app2\m1");
        $app2M1->setAppLabel('app2');
        $app2M1->setDependency('app1', "app1\m1");

        $graph = new Graph();
        $graph->addNode("app1\m1", $app1M1);
        $graph->addNode("app1\m2", $app1M2);
        $graph->addNode("app2\m1", $app2M1);

        $graph->addDependency("app1\m2", $app1M2->getDependency(), $app1M2);
        $graph->addDependency("app2\m1", $app2M1->getDependency(), $app2M1);
        $graph->addDependency("app1\m1", $app1M1->getDependency(), $app1M1);

        $this->expectException(CircularDependencyError::class);
        $graph->getDecedentsTree('app2', "app2\m1");
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     */
    public function testCircularGraphInReversePlan()
    {
        $app1M1 = new Migration("app1\m1");
        $app1M1->setAppLabel('app1');
        $app1M1->setDependency('app2', "app2\m1");

        $app1M2 = new Migration("app1\m2");
        $app1M2->setAppLabel('app1');
        $app1M2->setDependency('app1', "app1\m1");

        $app2M1 = new Migration("app2\m1");
        $app2M1->setAppLabel('app2');
        $app2M1->setDependency('app1', "app1\m1");

        $graph = new Graph();
        $graph->addNode("app1\m1", $app1M1);
        $graph->addNode("app1\m2", $app1M2);
        $graph->addNode("app2\m1", $app2M1);

        $graph->addDependency("app1\m2", $app1M2->getDependency(), $app1M2);
        $graph->addDependency("app2\m1", $app2M1->getDependency(), $app2M1);
        $graph->addDependency("app1\m1", $app1M1->getDependency(), $app1M1);

        $this->expectException(CircularDependencyError::class);
        $graph->getAncestryTree('app2', "app2\m1");
    }
}
