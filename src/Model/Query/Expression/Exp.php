<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Expression;



use Eddmash\PowerOrm\Model\Query\Aggregates\Count;

class Exp
{
    /**
     * @return Count
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function Count($expression, $distinct=false)
    {
        return new Count($expression, $distinct);
    }
}