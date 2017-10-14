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

use Doctrine\Common\Collections\ArrayCollection;

class Node implements \Countable
{
    protected $connector;
    protected $defaultConnector;

    protected $children;
    /**
     * @var bool
     */
    protected $negated;

    /**
     * {@inheritdoc}
     */
    public function __construct($children = [], $connector = null, $negated = false)
    {
        $this->children = empty($children) ? new ArrayCollection() : $children;
        $this->connector = is_null($connector) ? $this->defaultConnector : $connector;
        $this->negated = $negated;
    }

    public static function createObject($children = null, $connector = null, $negated = false)
    {
        return new static($children, $connector, $negated);
    }

    public function add($node, $connectorType, $squash = true)
    {
        if ($this->children->contains($node)):
            return $node;
        endif;
        if (!$squash):
            $this->children->add($node);

            return $node;
        endif;

        if ($connectorType == $this->connector):

            if ($node instanceof Node && !$node->isNegated() &&
                ($connectorType == $node->connector || count($node) == 1)
            ):
                $children = array_merge($node->getChildren()->toArray(), $this->getChildren()->toArray());
                $this->children = new ArrayCollection();
                foreach ($children as $child) :
                    $this->children->add($child);
                endforeach;


                return $this;
            else:
                $this->children->add($node);

                return $node;
            endif;
        else:
            //more or less of cloning this node
            $obj = new static($this->children, $this->connector, $this->negated);

            // update the connector to use btwn the current node and the passed in node
            $this->connector = $connectorType;

            // update the children
            $this->children = [$obj, $node];

            return $node;
        endif;
    }

    /**
     * @return ArrayCollection
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
     * Count elements of an object.
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     *             </p>
     *             <p>
     *             The return value is cast to an integer
     *
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->children);
    }

    /**
     * @return bool
     */
    public function isNegated()
    {
        return $this->negated;
    }

    public function __toString()
    {
        $children = Tools::stringify($this->children, false);
        $children = rtrim($children, ']');
        $children = ltrim($children, '[');
        $children = trim($children);
//        $children = trim($children, ",");
        return sprintf('(%s : %s)', $this->connector, $children);
    }
}
