<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/9/19
 * Time: 10:59 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestingApps\AutodetectorTest\Tests;

use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Exception\NodeNotFoundError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Tests\PowerormTest;
use Eddmash\PowerOrm\Tests\TestingApps\AutodetectorTest\AutodetectorTestApp;

class AutodetectorTest extends PowerormTest
{
    /**
     * @var BaseOrm
     */
    private $orm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orm = $this->getNewOrm(new Settings(
            [
                'components' => [
                    AutodetectorTestApp::class
                ],
            ]
        ));
    }

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

        try {
            $autodetector = new AutoDetector(
                $loader->getProjectState(),
                ProjectState::currentAppsState($registry),
                $asker,
                $this->orm
            );
        } catch (NodeNotFoundError $e) {
        } catch (TypeError $e) {
        }

        $changes = $autodetector->getChanges($loader->graph);
        $v=1;

    }
}
