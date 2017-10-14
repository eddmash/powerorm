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
use Eddmash\PowerOrm\Model\Query\Queryset;

const AND_CONNECTOR = 'AND';
const OR_CONNECTOR = 'OR';
const ORDER_PATTERN = '/\?|[-+]?[.\w]+$/';

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
function or_()
{
    return new Q(Queryset::formatFilterConditions(__FUNCTION__, func_get_args()), OR_CONNECTOR);
}
/**
 * @return Q
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function not_()
{
    return (new Q(Queryset::formatFilterConditions(__FUNCTION__, func_get_args())))->negate();
}

/**
 * @return Q
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function q_()
{
    return new Q(Queryset::formatFilterConditions(__FUNCTION__, func_get_args()));
}

/**
 * @return F
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function f_($name)
{
    return new F($name);
}
/**
 * @return Func
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function func_($name)
{
    return new Func($name);
}

/**
 * @return Value
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function value_($name)
{
    return new Value($name);
}
