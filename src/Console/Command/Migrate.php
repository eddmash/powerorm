<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Executor;
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 * Class Migrate.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migrate extends BaseCommand
{
    public $help = 'Shows all available migrations for the current project.';

    public function getPositionalOptions()
    {
        $option = parent::getPositionalOptions();
        $option['migrationName'] = 'Database state will be brought to the state after the given migration. ';

        return $option;
    }

    public function getOptions()
    {
        $option = parent::getOptions();
        $option['--fake'] = 'Mark migrations as run without actually running them.';

        return $option;
    }

    public function handle($argOpts = [])
    {
        $name = array_shift($argOpts);

        if ($name === '--fake' || in_array('--fake', $argOpts)):
            $fake = true;
            $name = null;
        else:
            $fake = false;
        endif;

        $connection = BaseOrm::getDbConnection();
        $registry = BaseOrm::getRegistry();

        $executor = Executor::createObject($connection);

        // target migrations to act on
        if (!empty($name)):
            if ($name == 'zero'):
                //todo confirm the migration exists
                $targets = [$name];
            else:
                $targets = $executor->loader->getMigrationByPrefix($name);
            endif;
        else:
            $targets = $executor->loader->graph->getLeafNodes();
        endif;
        

        // get migration plan
        $plan = $executor->getMigrationPlan($targets);

        $this->dispatchSignal('powerorm.migration.pre_migrate', $this);

        $this->info('Running migrations', true);

        if (empty($plan)):
            $this->normal('  No migrations to apply.', true);

            //detect if a makemigrations is required
            $auto_detector = new AutoDetector($executor->loader->getProjectState(), ProjectState::fromApps($registry));

            $changes = $auto_detector->getChanges($executor->loader->graph);

            if (!empty($changes)):

                $this->warning('  Your models have changes that are not yet reflected '.
                    "in a migration, and so won't be applied.", true);

                $this->warning("  Run 'manage.py makemigrations' to make new ".
                    "migrations, and then re-run 'manage.py migrate' to apply them.", true);

            endif;
        else:

            // migrate
            $executor->migrate($targets, $plan, $fake);

        endif;

        $this->dispatchSignal('powerorm.migration.post_migrate', $this);
    }
}
