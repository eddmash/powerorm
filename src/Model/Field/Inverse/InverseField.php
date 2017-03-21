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
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\OneToManyRel;
use Eddmash\PowerOrm\Model\Model;

//todo ensure the owning side actually exists
class InverseField extends RelatedField
{
    public $inverse = true;
    public $concrete = false;

    public function getRelatedFields()
    {

        if (is_string($this->relation->toModel)):
            throw new ValueError(sprintf('Related model "%s" cannot be resolved', $this->relation->toModel));
        endif;

        if ($this->fromField == BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT) :
            // we need this field to point to the primary key of the model which is an actual column on the database
            $this->fromField = $this->scopeModel->meta->primaryKey;

        endif;

        //end point of relation
        if (is_string($this->toField)):
            $this->toField = $this->relation->toModel->meta->getField($this->toField);
        endif;


        return [$this->fromField, $this->toField];
    }

    /**
     * @param Model $modelInstance
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getRelatedFilter(Model $modelInstance)
    {
        /** @var $fromField Field */
        /** @var $toField Field */
        list($fromField, $toField) = $this->getRelatedFields();

        echo $fromField."<br>";
        echo $toField."<br>";
        $value = $modelInstance->{$fromField->getAttrName()};

        return [$toField->getAttrName() => $value];
    }

    /**
     * @inheritDoc
     */
    public function getColumnName()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function dbType($connection)
    {
        return;
    }


    /**
     * @inheritDoc
     */
    public function contributeToInverseClass(Model $relatedModel, ForeignObjectRel $relation)
    {

    }

}