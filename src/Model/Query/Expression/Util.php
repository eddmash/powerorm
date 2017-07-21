<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Eddmash\PowerOrm\Model\Query\Aggregates\Count;
use Eddmash\PowerOrm\Model\Query\Q;


const AND_CONNECTOR = 'AND';
const OR_CONNECTOR = 'OR';

/**
 * @return Count
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function count_($expression, $distinct = false)
{
    return new Count($expression, $distinct);
}

/**
 * @return Q
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function or_($conditions)
{
    return new Q($conditions, OR_CONNECTOR);
}
/**
 * @return Q
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function not_($conditions)
{
    return (new Q($conditions))->negate();
}/**
 * @return Q
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function q_($conditions)
{
    return new Q($conditions);
}