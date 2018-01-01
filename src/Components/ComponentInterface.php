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

use Eddmash\PowerOrm\BaseOrm;

interface ComponentInterface
{
    /**
     * This method is invoked after the orm registry is ready .
     *
     * This means the models can be accessed within this model without any
     * issues.
     *
     * @param \Eddmash\PowerOrm\BaseOrm $baseOrm
     *
     * @return mixed
     */
    public function ready(BaseOrm $baseOrm);

    /**
     * True if it this component is accessible as an attribute of the orm.
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function isQueryable();

    /**
     * Instance to to return if the component is queryable.
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getInstance();

    /**
     * Name to use when querying this component.
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getName();

    /**
     * An array of Command classes that this component provides.
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getCommands();
}
