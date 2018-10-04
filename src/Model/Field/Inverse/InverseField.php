<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Inverse;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;

/**
 * THis fields are used to perform queries that move from the inverse side to the owning side.
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class InverseField extends RelatedField
{
    public $inverse = true;

    public $concrete = false;

    /**
     * {@inheritdoc}
     */
    public function getRelatedFields()
    {
        if (is_string($this->relation->toModel)) {
            throw new ValueError(sprintf('Related model "%s" cannot be resolved', $this->relation->toModel));
        }

        if (BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT == $this->fromField) {
            // we need this field to point to the primary key of the model which is an actual column on the database
            $this->fromField = $this->scopeModel->getMeta()->primaryKey;
        } elseif (is_string($this->fromField)) {
            $this->fromField = $this->relation->toModel->getMeta()->getField($this->fromField);
        }

        //end point of relation
        if (is_string($this->toField)) {
            $this->toField = $this->relation->getFromModel()->getMeta()->getField($this->toField);
        }

        return [$this->fromField, $this->toField];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function dbType(ConnectionInterface $connection)
    {
        return;
    }

    public function contributeToClass($fieldName, $modelObject)
    {
        if ($this->autoCreated) {
            parent::contributeToClass($fieldName, $modelObject);
        } else {
            Field::contributeToClass($fieldName, $modelObject);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToInverseClass(Model $relatedModel, ForeignObjectRel $relation)
    {
    }

    public function getJoinColumns()
    {
        return [$this->fromField, $this];
    }
}
