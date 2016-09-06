<?php
/**
 * Created by http://eddmash.com
 * User: edd
 * Date: 5/26/16
 * Time: 3:07 PM.
 */
namespace eddmash\powerorm\migrations;

use eddmash\powerorm\console\Base;
use eddmash\powerorm\exceptions\AmbiguityError;

/**
 * Runs migrations on the database.
 *
 * Class Executor
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Executor extends Base
{
    public $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->loader = new Loader($connection);
        $this->recorder = new Recorder($connection);
    }

    /**
     * Given a migration, get a list of needed to for the current to be run .
     *
     * @param $targets
     *
     * @return array
     */
    public function migration_plan($targets, $fresh_start = false)
    {
        $graph = $this->loader->graph;
        if ($fresh_start):
            $applied_migrations = [];
        else:
            $applied_migrations = $this->loader->applied_migrations;
        endif;

        $plan = [];
        foreach ($targets as $node_name) :

            if ($node_name == 'zero'):
                $roots = $this->loader->graph->root_nodes();
                foreach ($roots as $root) :
                    $descedants = $this->loader->graph->after_lineage($root);
                    foreach ($descedants as $descedant) :
                        if (in_array($descedant, $applied_migrations)):
                            $plan[] = ['migration' => $graph->nodes[$descedant], 'apply' => false];
                            unset($applied_migrations[$descedant]);
                        endif;

                    endforeach;

                endforeach;
            elseif (in_array($node_name, $applied_migrations)):
                // if its already applied, means we need to un apply i.e.
                // we need to rollback the migrations up to the state represented by the current target migration
                // so get its children and roll them back
                $children = $graph->node_family_tree[$targets[0]]->children;

                foreach ($children as $child) :
                    // get any migrations that depend on the current child
                    $after_lineage = $graph->after_lineage($child->name);

                    foreach ($after_lineage as $migration_name) :
                        if (in_array($migration_name, $applied_migrations)):
                            $plan[] = ['migration' => $graph->nodes[$migration_name], 'apply' => false];
                            // remove it from the list
                            unset($applied_migrations[$migration_name]);
                        endif;
                    endforeach;

                endforeach;
            else:
                $before_lineage = $graph->before_lineage($node_name);

                foreach ($before_lineage as $migration_name) :
                    if (!in_array($migration_name, $applied_migrations)):
                        $plan[] = ['migration' => $graph->nodes[$migration_name], 'apply' => true];
                    endif;
                endforeach;
            endif;
        endforeach;

        return $plan;
    }

    public function migrate($target, $plan, $fake)
    {
        if (empty($plan)):
            $plan = $this->migration_plan($target);
        endif;

        // applied migrations
        $applied_migrations_name = $this->loader->applied_migrations;

        $applied_migrations = [];

        //get actual applied migrations from the graph
        foreach ($applied_migrations_name as $item) :
            if (array_key_exists($item, $this->loader->graph->nodes)):
                $applied_migrations[$item] = $this->loader->graph->nodes[$item];
            endif;
        endforeach;

        $migrations_to_apply = [];
        $migrations_to_unapply = [];

        foreach ($plan as $item) :
            $migration = $item['migration'];

            if ($item['apply']):
                $migrations_to_apply[$migration->name] = $item;
            else:

                $migrations_to_unapply[$migration->name] = $item;
            endif;
        endforeach;

        // get the migration plan for the whole project
        // using this plan we update the state up until we have migrated all
        // the migrations in the $migrations_to_run above
        // we need this because some of the operations that migrations handle require a before state and an after state
        // i.e before the operation is appliend and after its applied
        $full_plan = $this->migration_plan($this->loader->graph->leaf_nodes(), true);

        if (!empty($migrations_to_unapply) && !empty($migrations_to_apply)):
            throw new AmbiguityError('Migration plans with both forwards and backwards migrations are not supported. '.
                'Please split your migration process into  separate plans of only forwards OR backwards migrations.');
        endif;

        if (!empty($migrations_to_apply)):
            $this->_update_migrations($plan, $full_plan, $fake);
        endif;

        if (!empty($migrations_to_unapply)):
            $this->_rollback_migrations($plan, $full_plan, $fake);
        endif;
    }

    /**
     * @param ProjectState $state
     * @param Migration    $migration
     * @param $fake
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function apply_migration($state, Migration $migration, $fake)
    {
        $this->normal('Applying '.$migration.' ... ');

        if (!$fake):
            $state = $migration->apply($state, $this->connection);
            if ($state):
                $this->recorder->record_applied(['name' => $migration->name]);
            endif;
            $this->success(' Success', true);
        else:
            // record the migration into the database
            $this->recorder->record_applied(['name' => $migration->name]);
            $this->success(' ... Faked', true);

        endif;

        return $state;
    }

    /**
     * @param ProjectState $state
     * @param Migration    $migration
     * @param $fake
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unapply_migration($state, $migration, $fake)
    {
        $this->normal('Unapplying '.$migration);
        if (!$fake):
            $state = $migration->unapply($state, $this->connection);
            if ($state):
                $this->recorder->record_unapplied(['name' => $migration->name]);
            endif;
            $this->success(' ... Success', true);
        else:
            // record the migration into the database
            $this->recorder->record_unapplied(['name' => $migration->name]);
            $this->success(' ... Faked', true);

        endif;
    }

    public function _update_migrations($plan, $full_plan, $fake)
    {

        // we need the project state so get it
        // we recreate the whole project state using the full migration plan
        // to ensure consistency
        $state = new ProjectState();

        $migrations_to_run = [];
        foreach ($plan as $item) :
            $migrations_to_run[$item['migration']->name] = $item['migration'];
        endforeach;

        foreach ($full_plan as $f_plan) :
            $migration = $f_plan['migration'];

            if (array_key_exists($migration->name, $migrations_to_run)):
                $state = $this->apply_migration($state, $migration, $fake);

                // remove it from the migrations to run
                unset($migrations_to_run[$migration->name]);
            else:
                // mutate state for consistency
                $migration->update_state($state);
            endif;

        endforeach;
    }

    public function _rollback_migrations($plan, $full_plan, $fake)
    {

        // we need the project state so get it
        $state = new ProjectState();

        // we need a copy of the project state before migration updates it
        $back_states_collection = [];

        $migrations_to_run = [];
        foreach ($plan as $item) :
            $migrations_to_run[$item['migration']->name] = $item['migration'];
        endforeach;

        foreach ($full_plan as $f_plan) :

            /*
             * If there are no migrations to run, just break
             */
            if (empty($migrations_to_run)):
                break;
            endif;

            $migration = $f_plan['migration'];

            if (array_key_exists($migration->name, $migrations_to_run)):
                //save state before update
                $back_states_collection[$migration->name] = $state->deep_clone();

                // remove it from the migrations to run
                unset($migrations_to_run[$migration->name]);
            endif;

            // update the state
            $migration->update_state($state);

        endforeach;

        // rollback now
        foreach ($plan as $item) :
            $migration = $item['migration'];
            $this->unapply_migration($back_states_collection[$migration->name], $migration, $fake);
        endforeach;
    }
}
