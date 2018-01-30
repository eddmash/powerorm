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

use Eddmash\PowerOrm\Model\Query\Expression\ExpResolverInterface;
use Eddmash\PowerOrm\Model\Query\Expression\Func;

class BaseAggregate extends Func
{
    private $containsAggregate = true;

    protected $name;

    /**
     * {@inheritdoc}
     */
    public function __construct($expression, array $kwargs = [])
    {
        $kwargs['expression'] = [$expression];
        parent::__construct($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveExpression(
        ExpResolverInterface $resolver,
        $allowJoins = true,
        $reuse = null,
        $summarize = false,
        $forSave = false
    ) {
        // Aggregates are not allowed in UPDATE queries, so ignore forSave
        $obj = parent::resolveExpression(
            $resolver,
            $allowJoins,
            $reuse,
            $summarize
        );

        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function containsAggregates()
    {
        return $this->containsAggregate;
    }

    public function getGroupByCols()
    {
        return [];
    }
}
