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

class ReverseManyToOneDescriptor extends BaseDescriptor
{
    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
    }
}
