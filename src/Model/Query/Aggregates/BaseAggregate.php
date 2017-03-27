<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Aggregates;

use Eddmash\PowerOrm\Model\Query\Expression\Func;

class BaseAggregate extends Func
{
    public $containsAggregate = true;
    protected $name;
}
