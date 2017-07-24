<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Eddmash\PowerOrm\Model\Field\Field;

interface ResolvableExpInterface
{
    /**
     * Provides the chance to do any preprocessing or validation before being added to the query.e.g.
     * in Exp::Count('username') we need the username to converted to an actual model field.
     *
     *
     *
     * @param ExpResolverInterface $resolver
     * @param bool                 $allowJoins
     * @param null                 $reuse
     * @param bool                 $summarize  s a boolean that, when True, signals that the query being computed is a terminal
     *                                         aggregate query
     * @param bool                 $forSave
     *
     * @return
     *
     * @internal param null $query
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function resolveExpression(ExpResolverInterface $resolver, $allowJoins = true, $reuse = null,
                                      $summarize = false, $forSave = false);
}
