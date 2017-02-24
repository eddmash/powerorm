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
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Node
{
    public $name;
    public $children;
    public $parent;

    public function __construct($name)
    {
        $this->name = $name;
        $this->children = [];
        $this->parent = [];
    }

    /**
     * @param Node $parent
     */
    public function addParent($parent)
    {
        $this->parent[] = $parent;
    }

    /**
     * @param Node $child
     */
    public function addChild($child)
    {
        $this->children[] = $child;
    }

    /**
     * Get ancestors from the first ancestor aka adam and eve to upto this node.that is oldest at index '0'.
     *
     * @return array
     */
    public function getAncestors($ignoreSelf = false)
    {
        $ancestors = [];

        if($ignoreSelf === false):
            $ancestors[] = $this->name;
        endif;

        /** @var $parent Node */
        foreach ($this->parent as $parent) :
            $ancestors = array_merge($parent->getAncestors(), $ancestors);
        endforeach;

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

        $descendants[] = $this->name;

        /** @var $child Node */
        foreach ($this->children as $child) :
            $descendants = array_merge($child->getDescendants(), $descendants);
        endforeach;

        return $descendants;
    }
}
