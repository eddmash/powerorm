<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/9/19
 * Time: 10:59 PM.
 */

namespace Eddmash\PowerOrm\Tests\Autodetector\Tests;

use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Exception\NodeNotFoundError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\Autodetector\AutodetectorApp;

class AutodetectorTest extends AppAwareTest
{

    /**
     * Checks if relatedName is being user on foreignkeys on reverse relation.
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     */
    public function testReverseForeignKeyFields()
    {
        $registry = $this->orm->getRegistryCache();


        $loader = new Loader($this->orm);

        $issues = $loader->detectConflicts();
        $asker = new InteractiveAsker(null, null);

        $autodetector = new AutoDetector(
            $loader->getProjectState(),
            ProjectState::currentAppsState($registry),
            $asker,
            $this->orm
        );


        $changes = $autodetector->getChanges($loader->graph);
        $v = 1;

    }

    protected function getComponents(): array
    {
        return [AutodetectorApp::class];
    }
}
