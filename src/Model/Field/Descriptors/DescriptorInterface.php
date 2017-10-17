<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Descriptors;

use Eddmash\PowerOrm\Model\Model;

/**
 * This class enables field classes to know how to get and set there values.
 *
 * Interface DescriptorInterface
 * @package Eddmash\PowerOrm\Model\Field\Descriptors
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
interface DescriptorInterface
{
    /**
     * @return mixed
     */
    public function getValue(Model $modelInstance);

    /**
     * @param mixed $value
     */
    public function setValue(Model $modelInstance, $value);
}
