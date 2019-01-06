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

use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Aggregates\Avg;
use Eddmash\PowerOrm\Model\Query\Aggregates\Count;
use Eddmash\PowerOrm\Model\Query\Aggregates\Max;
use Eddmash\PowerOrm\Model\Query\Aggregates\Min;
use Eddmash\PowerOrm\Model\Query\Aggregates\Sum;
use Eddmash\PowerOrm\Model\Query\Prefetch;
use Eddmash\PowerOrm\Model\Query\Q;
use Eddmash\PowerOrm\Model\Query\Queryset;

const AND_CONNECTOR = 'AND';
const OR_CONNECTOR = 'OR';
const ORDER_PATTERN = '/\?|[-+]?[.\w]+$/';

/**
 * @return Count
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function count_($expression, $distinct = false)
{
    return new Count($expression, $distinct);
}

/**
 * @param $name
 * @param $expression
 *
 * @return Ref
 */
function ref_($name, $expression)
{
    return new Ref($name, $expression);
}

/**
 * @param $expression
 *
 * @return Avg
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function avg_($expression)
{
    return new Avg($expression);
}

/**
 * @param $expression
 *
 * @return Min
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function min_($expression)
{
    return new Min($expression);
}

/**
 * @param $expression
 *
 * @return Max
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function max_($expression)
{
    return new Max($expression);
}

/**
 * @param $expression
 *
 * @return Sum
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function sum_($expression)
{
    return new Sum($expression);
}

/**
 * @return Q
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 */
function or_()
{
    return new Q(
        Queryset::formatConditions(
            __FUNCTION__,
            func_get_args()
        ), OR_CONNECTOR
    );
}

/**
 * @return Q
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 */
function not_()
{
    return (new Q(Queryset::formatConditions(__FUNCTION__, func_get_args())))->negate();
}

/**
 * @return Q
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 */
function q_()
{
    return new Q(
        Queryset::formatConditions(
            __FUNCTION__,
            func_get_args()
        )
    );
}

/**
 * @return F
 *
 * @since  1.1.0
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
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 */
function func_()
{
    return new Func(
        Queryset::formatConditions(
            __FUNCTION__,
            func_get_args()
        )
    );
}

/**
 * @return Value
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function value_($name)
{
    return new Value($name);
}

/**
 * @param Model[]        $instances
 * @param Prefetch|array $lookups
 *
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 * @throws \Eddmash\PowerOrm\Exception\ValueError
 */
function prefetchRelatedObjects_(array $instances, $lookups)
{
    Prefetch::prefetchRelatedObjects($instances, $lookups);
}
