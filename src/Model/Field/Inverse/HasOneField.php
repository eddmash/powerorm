<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Inverse;

use Eddmash\PowerOrm\Model\Model;

class HasOneField extends HasManyField
{
    public $unique = true;
    /**{inheritdoc}*/
    protected $descriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\ReverseOneToOneDescriptor';

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        $modelInstance->_fieldCache[$this->relation->getAccessorName()] = $this->getDescriptor();
    }
}
