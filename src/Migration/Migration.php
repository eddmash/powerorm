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

use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 * The base class for all migrations.
 *
 * Migration files will import this from Eddmash\PowerOrm\Migration\Migration and subclass it as a class
 * called Migration.
 *
 * It will have one or more of the following attributes:
 * - getOperations: A list of Operation instances, probably from Eddmash\PowerOrm\Migration\Migration\Operation.
 * - getDependency: A list of tuples of (app_path, migration_name)
 *
 * Note that all migrations come out of migrations and into the Loader or Graph as instances, having been
 * initialized with their app name.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migration implements MigrationInterface
{
    protected $name;
    protected $operations;
    protected $description;
    protected $dependency = [];

    public function __construct($name)
    {
        $this->name = $name;

        $this->operations = $this->getOperations();
        $this->requires = $this->getDependency();
    }

    public static function createObject($param)
    {
        return new static($param);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public static function createShortName($name)
    {

        $pos = strripos($name, '\\');
        if ($pos):
            $name = trim(substr($name, $pos), '\\');
        endif;

        return $name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Operations to apply during this migration, in order.
     *
     * @return mixed
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * @param mixed $operations
     */
    public function setOperations($operations)
    {
        $this->operations = $operations;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Operations to apply during this migration, in order.
     *
     * @return mixed
     */
    public function getDependency()
    {
        return $this->dependency;
    }

    /**
     * @param mixed $dependency
     */
    public function setDependency($dependency)
    {
        $this->dependency[] = $dependency;
    }

    /**
     * Takes a project_state representing all migrations prior to this one and a schema for a live database and
     * applies the migration  in a forwards order.
     *
     * Returns the resulting project state for efficient re-use by following Migrations.
     *
     * @param ProjectState $state
     * @param SchemaEditor $schemaEditor
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function apply($state, $schemaEditor)
    {

        /** @var $operation Operation */
        foreach ($this->operations as $operation) :
            // preserve state before operation
            $oldState = $state->deepClone();

            $operation->updateState($state);
            $schemaEditor->connection->transactional(function () use ($operation, $schemaEditor, $oldState, $state) {
                $operation->databaseForwards($schemaEditor, $oldState, $state);
            });
        endforeach;

        return $state;
    }

    /**
     *  Takes a project_state representing all migrations prior to this one and a schema for a live database and applies
     * the migration in a reverse order.
     *
     * The backwards migration process consists of two phases:
     *      1. The intermediate states from right before the first until right
     *         after the last operation inside this migration are preserved.
     *      2. The operations are applied in reverse order using the states recorded in step 1.
     *
     * @param ProjectState $state
     * @param SchemaEditor $schemaEditor
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unApply($state, $schemaEditor)
    {

        // we
        $itemsToRun = [];

        // Phase 1 --
        /* @var $operation Operation */
        /** @var $newState ProjectState */
        $newState = $state;
        // we need to reverse the operations so that foreignkeys are removed before model is destroyed
        foreach ($this->operations as $operation) :
            //Preserve new state from previous run to not tamper the same state over all operations
            $newState = $newState->deepClone();
            $oldState = $newState->deepClone();
            $operation->updateState($newState);
            /*
             * we insert them in the reverse order so the last operation is run first
             */
            array_unshift($itemsToRun, ['operation' => $operation, 'oldState' => $oldState, 'newState' => $newState]);
        endforeach;

        // Phase 2 -- Since we are un applying the old state is where we want to go back to
        //   and the new state is where we are moving away from i.e
        //   we are moving from $newState to $oldState

        foreach ($itemsToRun as $runItem) :

            $schemaEditor->connection->transactional(function () use ($runItem, $schemaEditor) {
                /** @var $operation Operation */
                $operation = $runItem['operation'];
                $operation->databaseBackwards($schemaEditor, $runItem['newState'], $runItem['oldState']);
            });
        endforeach;

        return $state;
    }

    /**
     * Takes a ProjectState and returns a new one with the migration's operations applied to it.
     *
     * Preserves the original object state by default and will return a mutated state from a copy.
     *
     * @param ProjectState $state
     * @param bool|true    $preserveState
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function updateState($state, $preserveState = true)
    {
        $newState = $state;
        if ($preserveState):
            $newState = $state->deepClone();
        endif;

        /** @var $operation Operation */
        foreach ($this->operations as $operation) :

            $operation->updateState($newState);

        endforeach;

        return $newState;
    }

    public function __toString()
    {
        return sprintf('<Migration %s>', $this->name);
    }
}
