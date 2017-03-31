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

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Model;

class ForeignObjectRel extends BaseObject
{
    public $autoCreated = true;
    public $isRelation = true;
    public $concrete = false;
    public $multiple = true;

    // Reverse relations are always nullable (PowerOrm can't enforce that a
    // foreign key on the related model points to this model).
    public $null = true;

    /**
     * The model to which the scopeModel is related.
     *
     * @var Model
     */
    public $toModel;
    /**
     * @var Model
     */
    public $through;

    /**
     * @var RelatedField
     */
    public $fromField;

    public $parentLink;

    public $onDelete;

    /**
     * The name to use for the relation from the related object back to this one.
     * It’s also the default value for related_query_name
     * (the name to use for the reverse filter name from the target model).
     *
     * If you’d prefer Django not to create a backwards relation, set related_name to '+' or end it with '+'.
     *
     * @var
     */
    public $relatedName;

    /**
     * The name to use for the reverse filter name from the target model.
     * It defaults to the value of related_name or default_related_name if set,
     * otherwise it defaults to the name of the model.
     *
     * @var
     */
    public $relatedQueryName;

    public $name;

    public function __construct($kwargs = [])
    {
        BaseOrm::configure($this, $kwargs, ['to' => 'toModel']);
    }

    /**
     * Name of the field.
     *
     * @return string
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getName()
    {
        return $this->fromField->getRelatedQueryName();
    }

    public static function createObject($kwargs = [])
    {
        return new static($kwargs);
    }

    public function getToModel()
    {
        return $this->toModel;
    }

    public function getFromModel()
    {
        return $this->fromField->scopeModel;
    }

    public function isManyToMany()
    {
        return $this->fromField->manyToMany;
    }

    public function isOneToMany()
    {
        return $this->fromField->oneToMany;
    }

    public function isManyToOne()
    {
        return $this->fromField->manyToOne;
    }

    public function isOneToOne()
    {
        return $this->fromField->oneToOne;
    }

    public function getJoinColumns()
    {
        return $this->fromField->getReverseJoinColumns();
    }

    public function getLookup($name)
    {
        return $this->fromField->getLookup($name);
    }

    public function getAccessorName(Model $model = null)
    {
        if (is_null($model)) :
            $model = $this->getFromModel();
        endif;

        if ($this->relatedName) :
            return $this->relatedName;
        endif;

        return sprintf('%s_set', strtolower($model->meta->getModelName()));
    }

    public function getPathInfo()
    {
        return $this->fromField->getReversePathInfo();
    }

//    public function __toString()
//    {
//        return sprintf('<%s %s>', static::class, $this->toModel->meta->modelName);
//    }
}
