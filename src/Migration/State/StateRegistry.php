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
use Eddmash\PowerOrm\Exception\CircularDependencyError;
use Eddmash\PowerOrm\Exception\OrmException;
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
        // we need to order in away that parents are created before children
        // remember this models are created in the _Fake namespace hence
        // they don't exist.
        $creationOrder = [];
        /** @var $modelState ModelState */
        foreach ($modelStates as $name => $modelState) :
            $creationOrder[$modelState->name] = [];
            if ($modelState->extends):
                $creationOrder[$modelState->name][] = $modelState->extends;
            endif;
        endforeach;
        try {
            $creationOrder = Tools::topologicalSort($creationOrder);
        } catch (CircularDependencyError $e) {
            throw new OrmException(static::class . '::' . $e->getMessage());
        }

        foreach ($creationOrder as $depend) :
            $modelState = $modelStates[$depend];
            $modelState->toModel($this);
        endforeach;
        $this->ready = true;
    }
}
