<?php

namespace eddmash\powerorm\console\command;

use eddmash\powerorm\migrations\AutoDetector;
use eddmash\powerorm\migrations\Executor;
use eddmash\powerorm\migrations\ProjectState;
use Orm;

/**
 * Class Migrate.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migrate extends Command
{
    public $help = 'Shows all available migrations for the current project.';

    public function get_positional_options()
    {
        $option = parent::get_positional_options();
        $option['migration_name'] = 'Database state will be brought to the state after the given migration. ';

        return $option;
    }

    public function get_options()
    {
        $option = parent::get_options();
        $option['--fake'] = 'Mark migrations as run without actually running them.';

        return $option;
    }

    public function handle($arg_opts = [])
    {
        $name = array_shift($arg_opts);

        if ($name === '--fake' || in_array('--fake', $arg_opts)):
            $fake = true;
            $name = null;
        else:
            $fake = false;
        endif;

        $connection = Orm::dbconnection();
        $registry = Orm::get_registry();

        $executor = new Executor($connection);

        // target migrations to act on
        if (!empty($name)):
            if ($name == 'zero'):
                //todo confirm the migration exists
                $target = [$name];
            else:
                $target = $executor->loader->get_migration_by_prefix($name);
            endif;
        else:
            $target = $executor->loader->graph->leaf_nodes();
        endif;

        // get migration plan
        $plan = $executor->migration_plan($target);

        $this->dispatch_signal('powerorm.migration.pre_migrate', $this);

        $this->info('Running migrations', true);

        if (empty($plan)):
            $this->normal('  No migrations to apply.', true);

            //detect if a makemigrations is required
            $auto_detector = new AutoDetector($executor->loader->get_project_state(), ProjectState::from_apps($registry));

            $changes = $auto_detector->changes($executor->loader->graph);

            if (!empty($changes)):

                $this->warning('  Your models have changes that are not yet reflected '.
                    "in a migration, and so won't be applied.", true);

                $this->warning("  Run 'manage.py makemigrations' to make new ".
                    "migrations, and then re-run 'manage.py migrate' to apply them.", true);

            endif;
        else:

            // migrate
            $executor->migrate($target, $plan, $fake);

        endif;

        $this->dispatch_signal('powerorm.migration.post_migrate', $this);
    }
}
