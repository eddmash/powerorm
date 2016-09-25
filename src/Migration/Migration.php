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

    public function apply() {

    }

    public function unApply() {

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
//        if($preserveState):
//            $newState = $state->deepClone();
//        endif;

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
