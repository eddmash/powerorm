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
use function Eddmash\PowerOrm\Model\Query\Expression\or_;
use function Eddmash\PowerOrm\Model\Query\Expression\not_;

class Q extends Node
{
    protected $defaultConnector = AND_CONNECTOR;

    protected $negated = false;

    /**
     * {@inheritdoc}
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

    protected function combine($other, $connector)
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

    public function not_($other)
    {
        $obj = $this->combine(not_($other), $this->connector);

        return $obj;
    }
}
