<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Helpers;


class Node implements \Countable
{

    protected $connector;
    protected $defaultConnector;

    protected $children;
    /**
     * @var bool
     */
    private $negated;

    /**
     * @inheritDoc
     */
    public function __construct($children = [], $connector = null, $negated = false)
    {
        $this->children = is_null($children) ? [] : $children;
        $this->connector = is_null($connector) ? $this->defaultConnector : $connector;
        $this->negated = $negated;
    }


    public static function createObject($children = null, $connector = null, $negated = false)
    {
        return new static($children, $connector, $negated);
    }


    public function add($node, $connector, $squash = true)
    {
        if (array_search($node, $this->children)):
            return $node;
        endif;
        if (!$squash):
            $this->children[] = $node;
            return $node;
        endif;

        if ($connector == $this->connector):
            dump("in");
            if ($node instanceof Node && !$this->negated &&
                ($connector == $this->connector || count($node) == 1)
            ):
                dump("inner");
                $this->children = array_merge($this->getChildren(), $node->getChildren());
                return $this;
            else:
                $this->children[] = $node;
                return $node;
            endif;
        else:
            //more or less of cloning this node
            $obj = new static($this->children, $this->connector, $this->negated);

            // update the connector to use btwn the current node and the passed in node
            $this->connector = $connector;

            // update the children
            $this->children = [$obj, $node];
            return $node;
        endif;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return mixed
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->children);
    }
}