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
use Eddmash\PowerOrm\Helpers\Tools;

class StateRegistry extends Registry
{
    /**
     * {@inheritdoc}
     */
    public function __construct($modelStates)
    {
        parent::__construct();

        $this->hydrate($modelStates);
    }

    /**
     * {@inheritdoc}
     */
    public static function createObject($modelStates = [])
    {
        return new static($modelStates);
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate($modelStates)
    {
        /** @var $modelState ModelState */
        $depends = [];
        foreach ($modelStates as $name => $modelState) :
            $extends = ($modelState->extends) ? [$modelState->extends] : [];
            $depends[$name] = $extends;

        endforeach;
        
        $orderedClasses = Tools::topologicalSort($depends);

        foreach ($orderedClasses as $class) :
            $modelState = $modelStates[$class];
            $modelState->toModel($this);
        endforeach;
        $this->ready = true;
    }
}
