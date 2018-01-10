<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

interface ContributorInterface
{
    /**
     * Add the current object to the passed in object.
     *
     * @param string $propertyName the name map the current object to, in the class object passed in
     * @param object $classObject  the object to attach the current object to
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contributeToClass($propertyName, $classObject);
}
