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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 * End-to-end migration execution - loads migrations, and runs them up or down to a specified set of targets.
 *
 * @since 1.0.1
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Executor extends BaseObject
{
    /**
     * @var Loader
     */
    public $loader;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SchemaEditor
     */
    private $schemaEditor;

    /**
     * @var Recorder
     */
    private $recorder;

    /**
     * Executor constructor.
     *
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->schemaEditor = SchemaEditor::createObject($connection);
        $this->loader = Loader::createObject($connection);
        $this->recorder = Recorder::createObject($connection);
    }

    /**
     * @param Connection $connection
     *
     * @return static
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($connection)
    {
        return new static($connection);
    }

    /**
     * Given a set of targets, returns a list of (Migration instance, backwards?).
     *
     * @param $targets
     * @param bool $cleanStart
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigrationPlan($targets, $cleanStart = false)
    {
        $plan = [];

        $targets = is_array($targets) ? $targets : [$targets];

        if ($cleanStart):
            $applied = []; else:
            $applied = $this->loader->appliedMigrations;
        endif;

        foreach ($targets as $target) :
            // if target is 'zero' unmigrate all
            if ($target == 'zero'):

                foreach ($this->loader->graph->getRootNodes() as $rootNode) :
                    foreach ($this->loader->graph->getDecedentsTree($rootNode) as $migrationName) :
                        if (in_array($migrationName, $applied)):
                            $plan[$migrationName] = [
                                'migration' => $this->loader->graph->getMigration($migrationName),
                                'unapply' => true,
                            ];
        unset($applied[$migrationName]);
        endif;
        endforeach;
        endforeach; elseif (in_array($target, $applied)):

                // if its applied then we need to unapply it.

                /** @var $childNode Node */
                foreach ($this->loader->graph->getNodeFamilyTree($target)->children as $childNode) :
                    foreach ($this->loader->graph->getDecedentsTree($childNode->name) as $migrationName) :
                        if (in_array($migrationName, $applied)):
                            $plan[$migrationName] = [
                                'migration' => $this->loader->graph->getMigration($migrationName),
                                'unapply' => true,
                            ];
        unset($applied[$migrationName]);
        endif;
        endforeach;

        endforeach; else:
                // if not applied and its not target is not zero, then apply it.
                foreach ($this->loader->graph->getAncestryTree($target) as $migrationName) :
                    if (!in_array($migrationName, $applied)):
                        $plan[$migrationName] = [
                            'migration' => $this->loader->graph->getMigration($migrationName),
                            'unapply' => false,
                        ];
        $applied[] = $migrationName;
        endif;
        endforeach;
        endif;
        endforeach;

        return $plan;
    }

    /**
     * Migrates the database up to the given targets.
     *
     * @param $targets
     * @param $plan
     * @param $fake
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function migrate($targets, $plan, $fake)
    {
        if (empty($plan)):
            $plan = $this->getMigrationPlan($targets);
        endif;

        $migrationsToRun = $this->getMigrationsFromPlan($plan);

        // the full plan that would be executed if we to run on a new database
        $fullPlan = $this->getMigrationsFromPlan($this->getMigrationPlan($this->loader->graph->getLeafNodes(), true));

        // Holds all states right before a migration is applied
        // if the migration is being run.
        $states = [];
        $state = ProjectState::createObject();

        //Phase 1 -- create all project states before a migration is (un)applied
        /** @var $migration Migration */
        foreach ($fullPlan as $migName => $migration) :
            // we use the migration to mutate state
            // after we mutate we remove the migration from the $migrationsToRun list.
            // so if we get to a point where we dont have any more $migrationsToRun break
            // this is to avoid any further mutations by other migrations not in the list.
            if (empty($migrationsToRun)):
                break;
        endif;

        $run = ArrayHelper::hasKey($migrationsToRun, $migName);
        if ($run):
                $states[$migName] = $state->deepClone();
        unset($migrationsToRun[$migName]);
        endif;

            // $run will be false if the migration is not in the $migrationsToRun list
            // so there is not need to preserve state else if its in the list we need to  we will get a new state object
            // that has been altered by the migration.
            // we do this because we need the object stored in the states array in the condition it was right before
            // the migration was applied.
            // remember in PHP objects are passed by reference.
            $state = $migration->updateState($state, $run);
        endforeach;

        // Phase 2 -- Run the migrations
        foreach ($plan as $mName => $migrationMeta) :

            if ($migrationMeta['unapply']):
                $this->unApplyMigration($states[$mName], $migrationMeta['migration'], $fake); else:
                $this->applyMigration($states[$mName], $migrationMeta['migration'], $fake);
        endif;
        endforeach;
    }

    /**
     * Rolls back the migrations on the database.
     *
     * @param ProjectState $state     this is the state before the migration is applied
     * @param Migration    $migration the migration to apply
     * @param bool         $fake
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unApplyMigration($state, $migration, $fake = false)
    {
        Console::stdout(sprintf(' UnApplying %s...', $migration->getName()));
        if (!$fake):
            $state = $migration->unApply($state, $this->schemaEditor);
        endif;

        $this->recorder->recordUnApplied(['name' => $migration->getName()]);

        if ($fake):
            $end = Console::ansiFormat('FAKED', [Console::FG_GREEN]); else:
            $end = Console::ansiFormat('OK', [Console::FG_GREEN]);
        endif;

        Console::stdout($end.PHP_EOL);

        return $state;
    }

    /**
     * Applies the migration to the database.
     *
     * @param ProjectState $state     this is the state before the migration is applied
     * @param Migration    $migration the migration to apply
     * @param bool         $fake
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function applyMigration($state, $migration, $fake = false)
    {
        Console::stdout(sprintf(' Applying %s...', $migration->getName()));
        if (!$fake):
            $state = $migration->apply($state, $this->schemaEditor);
        endif;

        $this->recorder->recordApplied(['name' => $migration->getName()]);

        if ($fake):
            $end = Console::ansiFormat('FAKED', [Console::FG_GREEN]); else:
            $end = Console::ansiFormat('OK', [Console::FG_GREEN]);
        endif;

        Console::stdout($end.PHP_EOL);

        return $state;
    }

    private function getMigrationsFromPlan($plan)
    {
        $migrations = [];
        foreach ($plan as $name => $migrationArr) :
            $migrations[$name] = $migrationArr['migration'];
        endforeach;

        return $migrations;
    }
}
