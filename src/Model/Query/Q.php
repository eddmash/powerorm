<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query;


use Eddmash\PowerOrm\Helpers\Node;

const AND_CONNECTOR = 'AND';
const OR_CONNECTOR = 'OR';

class Q extends Node
{
    protected $defaultConnector = AND_CONNECTOR;

    private $negated=false;

    /**
     * @inheritDoc
     */
    public function __construct($children)
    {
        parent::__construct(array_chunk($children, 1, true));
    }


    public function negate()
    {
        $this->negated = ! $this->negated;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNegated()
    {
        return $this->negated;
    }
}