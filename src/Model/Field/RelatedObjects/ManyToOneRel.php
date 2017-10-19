<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field\RelatedObjects;

use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * {@inheritdoc}
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ManyToOneRel extends ForeignObjectRel
{
    public $fieldName;

    /**
     * Returns the Field in the 'toModel' object to which this relationship is tied.
     *
     * @since 1.1.0
     *
     * @return Field
     *
     * @throws FieldDoesNotExist
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRelatedField()
    {
        $field = $this->toModel->meta->getField($this->fieldName);
        if (!$field->concrete):
            throw new FieldDoesNotExist(sprintf("No related field named '%s'", $this->fieldName));
        endif;

        return $field;
    }
}
