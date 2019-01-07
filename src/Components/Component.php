<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Components;

abstract class Component implements ComponentInterface
{
    /**
     * True if it this component is accessible as an attribute of the orm.
     *
     * @return bool
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function isQueryable()
    {
        return false;
    }

    /**
     * Instance to return if the component is queryable.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getInstance()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return [];
    }

    /**
     * Name to use when querying this component.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getName()
    {
        $ref = new \ReflectionObject($this);
        $name = $ref->getNamespaceName();
        $name = rtrim($name, '\\');

        return str_replace('\\', '_', strtolower($name));
    }
}
