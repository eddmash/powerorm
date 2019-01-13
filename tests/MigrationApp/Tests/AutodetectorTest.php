<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/9/19
 * Time: 10:59 PM.
 */

namespace Eddmash\PowerOrm\Tests\MigrationApp\Tests;

use Eddmash\PowerOrm\Console\Command\Makemigrations;
use Eddmash\PowerOrm\Console\Question\NonInteractiveAsker;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\MigrationInterface;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\MetaApp\MetaApp;
use Eddmash\PowerOrm\Tests\MigrationApp\MigrationApp;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

class AutodetectorTest extends AppAwareTest
{
    private $changes;

    protected function setUp(): void
    {
        parent::setUp();
        $registry = $this->orm->getRegistryCache();

        $loader = new Loader($this->orm);

        $issues = $loader->detectConflicts();
        $asker = new NonInteractiveAsker(null, null);

        $autodetector = new AutoDetector(
            $loader->getProjectState(),
            ProjectState::currentAppsState($registry),
            $asker,
            $this->orm
        );

        $this->changes = $autodetector->getChanges($loader->graph);

        $v = 0;
//        $def = new InputDefinition();
//        $def->addOption(new InputOption(
//            'dry-run',
//            null,
//            InputOption::VALUE_NONE));

//        Makemigrations::writeMigrations($this->changes,
//            new ArrayInput(array('--dry-run' => false), $def),
//            new NullOutput(), $this->orm);
    }

    public function testAppChanges()
    {
        $this->assertNotEmpty($this->changes);
        $this->assertCount(2, $this->changes);
        $this->assertArrayHasKey(MigrationApp::class, $this->changes);
        $this->assertArrayHasKey(MetaApp::class, $this->changes);
    }

    public function testAppOperations()
    {
        $appChanges = $this->changes[MigrationApp::class];

        $this->assertCount(1, $appChanges);

        /** @var $migration MigrationInterface */
        $migration = $appChanges[0];
        $this->assertInstanceOf(MigrationInterface::class, $migration);

        $operations = $migration->getOperations();
        $dependency = $migration->getDependency();

        $this->assertCount(4, $operations);
        $this->assertCount(2, $dependency);
    }

    protected function getComponents(): array
    {
        return [MigrationApp::class, MetaApp::class];
    }
}
