<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

/**
 * This class represents a migration and its family tree infomartion
 * i.e what migrations need to exist before this one can exist (parent)
 * and which migrations cannot exist if this one does not (children).
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Node
{
    public $name;

    /**
     * @var Node[]
     */
    public $children = [];

    /**
     * @var Node[]
     */
    public $parent = [];

    public $appName;

    public function __construct($appName, $name)
    {
        $this->name = $name;
        $this->children = [];
        $this->parent = [];
        $this->appName = $appName;
    }

    /**
     * @param Node $parent
     */
    public function addParent(self $parent)
    {
        $this->parent[] = $parent;
    }

    /**
     * @param Node $child
     */
    public function addChild(self $child)
    {
        $this->children[] = $child;
    }

    /**
     * Get ancestors from the first ancestor aka adam and eve to upto this
     * node.that is oldest at index '0'.
     *
     * @return array
     */
    public function getAncestors($ignoreSelf = false)
    {
        $ancestors = [];

        if (false === $ignoreSelf) {
            $ancestors[$this->name] = $this->appName;
        }

        /** @var $parent Node */
        foreach ($this->parent as $parent) {
            $parentAncenstors = $parent->getAncestors();

            $ancestors = array_merge($parentAncenstors, $ancestors);
        }

        return $ancestors;
    }

    /**
     * Get all nodes the consider this node there first ancestor, including this one.
     * This puts the last child as the first element on returned array while this node becomes the last.
     *
     * @return array
     */
    public function getDescendants()
    {
        $descendants = [];

        $descendants[$this->name] = $this->appName;

        /** @var $child Node */
        foreach ($this->children as $child) {
            $childDescendats = $child->getDescendants();
            $descendants = array_merge($childDescendats, $descendants);
        }

        return $descendants;
    }

    public function getNameWithApp()
    {
        return sprintf('%s_%s', $this->appName, $this->name);
    }
}
