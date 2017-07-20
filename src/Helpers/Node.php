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


class Node
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
    public function __construct($children=null, $connector=null, $negated = false)
    {
        $this->children = $children;
        $this->connector = ($connector||$this->defaultConnector);
        $this->negated = $negated;
    }


    public static function createObject($children=null, $connector=null, $negated = false)
    {
        return new static($children, $connector, $negated);
    }


    public function add(Node $node)
    {
        $this->children[] = $node;
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
}