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

class ProjectState
{
    /**
     * Takes in an Registry and returns a ProjectState matching it.
     *
     * @param Registry $registry
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

        return new static($modelStates);
    }
}
