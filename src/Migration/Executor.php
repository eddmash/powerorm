<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

class Executor
{
    /**
     * @var Loader
     */
    public $loader;

    public function __construct()
    {
        $this->loader = Loader::createObject();
    }

    public function getMigrationPlan($target)
    {
        return [];
    }

    public function migrate($target, $plan, $fake)
    {

    }
}
