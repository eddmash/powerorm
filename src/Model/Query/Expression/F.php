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

class F extends Combinable implements ResolvableExpInterface
{
    public $name;

    /**
     * {@inheritdoc}
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Provides the chance to do any preprocessing or validation before being added to the query.e.g.
     * in Exp::Count('username') we need the username to converted to an actual model field.
     *
     * @param ExpResolverInterface $resolver
     * @param bool                 $allowJoins
     * @param null                 $reuse
     * @param bool                 $summarize
     * @param bool                 $forSave
     *
     * @return
     *
     * @internal param null $query
     *
     * @author   : Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function resolveExpression(
        ExpResolverInterface $resolver,
        $allowJoins = true,
        $reuse = null,
        $summarize = false,
        $forSave = false
    ) {
        return $resolver->resolveExpression($this->name, $allowJoins, $reuse, $summarize);
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo()
    {
        $data = parent::__debugInfo();
        $data['name'] = $this->name;

        return $data;
    }
}
