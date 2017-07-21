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
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;
use const Eddmash\PowerOrm\Model\Query\Expression\OR_CONNECTOR;


class Q extends Node
{
    protected $defaultConnector = AND_CONNECTOR;

    private $negated = false;

    /**
     * @inheritDoc
     */
    public function __construct($children = [], $connector = null, $negated = false)
    {
        $items = [];
        foreach ($children as $name => $child) :
            if ($child instanceof Node):
                $items[] = $child;
            else:
                $items[] = [$name => $child];
            endif;
        endforeach;
        parent::__construct($items, $connector, $negated);
    }


    public function negate()
    {
        $this->negated = !$this->negated;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNegated()
    {
        return $this->negated;
    }

    protected function combine(Q $other, $connector)
    {
        $obj = new static();
        $obj->connector = $connector;
        $obj->add($other, $connector);
        $obj->add($this, $connector);
        return $obj;
    }

    public function or_($other)
    {
        return $this->combine($other, OR_CONNECTOR);
    }

    public function and_($other)
    {
        return $this->combine($other, AND_CONNECTOR);
    }

    protected function _negate_()
    {
        $obj = new static();
        $obj->add($this, AND_CONNECTOR);
        $obj->negate();
        return $obj;
    }
}