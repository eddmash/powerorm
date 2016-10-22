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

class StateRegistry extends Registry
{
    /**
     * {@inheritdoc}
     */
    public function __construct($modelStates) {
        parent::__construct();

        $this->_populate($modelStates);
    }

    /**
     * {@inheritdoc}
     */
    public static function createObject($modelStates) {
        return new static($modelStates);
    }

    /**
     * {@inheritdoc}
     */
    protected function _populate($modelStates)
    {
        /** @var $modelState ModelState */
        foreach ($modelStates as $name => $modelState) :
            $modelState->toModel($this);
        endforeach;

    }

}
