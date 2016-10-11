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

use Doctrine\DBAL\Schema\Schema;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;

class Migration
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

    public static function createObject($param) {
        return new static($param);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
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
     * @param Schema       $schema
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function apply($state, $schema) {

        /** @var $operation Operation */
        foreach ($this->operations as $operation) :
            // preserve state before operation
            $oldState = $state->deepClone();
            $operation->updateState($state);
            $operation->databaseForwards($schema, $oldState, $state);
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
     * @param Schema       $schema
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unApply($state, $schema) {

        // we
        $itemsToRun = [];

        // Phase 1 --
        /** @var $operation Operation */
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

        // Phase 2 --
        foreach ($itemsToRun as $runItem) :
            $operation = $runItem['operation'];
            $oldState = $runItem['oldState'];
            $newState = $runItem['newState'];
            $operation->databaseBackwards($schema, $oldState, $newState);
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
    public function updateState($state, $preserveState = true) {
        $newState = $state;
        if($preserveState):
            $newState = $state->deepClone();
        endif;

        /** @var $operation Operation */
        foreach ($this->operations as $operation) :

            $operation->updateState($state);

        endforeach;

        return $state;
    }

    public function __toString()
    {
        return sprintf('<Migration %s>', $this->name);
    }
}
