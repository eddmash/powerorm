<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Descriptors;

use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ForeignKey;
use Eddmash\PowerOrm\Model\Model;

class BaseDescriptor implements DescriptorInterface
{
    protected $reverse;

    /**
     * @var ForeignKey
     */
    protected $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    /**
     * @return mixed
     */
    public function getValue(Model $modelInstance)
    {
        return $modelInstance->_valueCache[$this->field->getAttrName()];
    }

    /**
     * @param mixed $value
     */
    public function setValue(Model $modelInstance, $value)
    {
        $modelInstance->_valueCache[$this->field->getAttrName()] = $value;
    }

    public function setReverse($status)
    {
        $this->reverse = $status;
    }
}
