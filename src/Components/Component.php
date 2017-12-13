<?php

/**
 * This file is part of the powercomponents package.
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function isQueryable()
    {
        return false;
    }

    /**
     * Instance to to return if the component is queryable.
     *
     * @return mixed
     *
     * @since 1.1.0
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
}
