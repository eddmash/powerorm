<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\State;

use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\DeconstructableObject;

class ProjectState extends DeconstructableObject
{
    public $modelStates;
    private static $registry;

    public function __construct($modelStates = [])
    {
        $this->modelStates = $modelStates;
    }

    /**
     * @param $models
     *
     * @return ProjectState
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($models = [])
    {
        return new static($models);
    }

    /**
     * Takes in an Registry and returns a ProjectState matching it.
     *
     * @param Registry $registry
     *
     * @return ProjectState
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function fromApps($registry)
    {
        $modelStates = [];
        foreach ($registry->getModels() as $modelName => $modelObj) :
            $modelStates[$modelName] = ModelState::fromModel($modelObj);
        endforeach;

        return static::createObject($modelStates);
    }

    /**
     * @param ModelState $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addModelState($model)
    {
        $this->modelStates[$model->name] = $model;
    }

    /**
     * Remove a model from the model state registry.
     *
     * @param $modelName
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function removeModelState($modelName)
    {
        unset($this->modelStates[$modelName]);
    }

    /**
     * @return StateRegistry
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRegistry()
    {
        return StateRegistry::createObject($this->modelStates);
    }

    /**
     * @return array
     */
    public function getModelStates()
    {
        return $this->modelStates;
    }

    public function deepClone()
    {
        $modelStates = [];

        /** @var $modelState ModelState */
        foreach ($this->modelStates as $name => $modelState) :
            $modelStates[$name] = $modelState->deepClone();
        endforeach;

        return static::createObject($modelStates);

    }

    public function deconstruct()
    {

    }

}
