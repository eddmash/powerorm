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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

class ForeignObjectRel extends Object
{
    public $autoCreated = true;
    public $isRelation = true;

    // Reverse relations are always nullable (PowerOrm can't enforce that a
    // foreign key on the related model points to this model).
    public $null = true;

    /**
     * @var Model
     */
    public $model;

    /**
     * @var Field
     */
    public $field;
    public $parentLink;
    public $onDelete;

    public function __construct($kwargs = [])
    {
        BaseOrm::configure($this, $kwargs, ['to' => 'model']);
    }

    public static function createObject($kwargs = [])
    {
        return new static($kwargs);
    }

    public function getRemoteField()
    {
        return $this->field;
    }

    public function getRelatedModel()
    {
        if ($this->field->scopeModel == null):
            throw new OrmException(
                "This method can't be accessed before field contributeToClass has been called.");
        endif;

        return $this->field->scopeModel;
    }

    public function isManyToMany()
    {
        return $this->field->manyToMany;
    }

    public function isOneToMany()
    {
        return $this->field->oneToMany;
    }

    public function isManyToOne()
    {
        return $this->field->manyToOne;
    }

    public function isOneToOne()
    {
        return $this->field->oneToOne;
    }
}
