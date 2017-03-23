<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\Inverse\HasManyField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedExact;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedGreaterThan;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedGreaterThanOrEqual;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedIn;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedIsNull;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedLessThan;
use Eddmash\PowerOrm\Model\Lookup\Related\RelatedLessThanOrEqual;
use Eddmash\PowerOrm\Model\Model;

/**
 * Base class that all relational fields inherit from.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelatedField extends Field
{
    /**
     * The field on the related object that the relation is to.
     * By default, The Orm uses the primary key of the related object.
     *
     * @var
     */
    public $toField;

    /**
     * points to the current field instance.
     *
     * @var string
     */
    public $fromField;

    public function __construct($kwargs = [])
    {
        if (!ArrayHelper::hasKey($kwargs, 'to')):
            throw new TypeError(sprintf("missing 1 required argument: 'to' for %s", static::class));
        endif;
        parent::__construct($kwargs);
    }

    public function checks()
    {
        $checks = parent::checks();
        $checks = array_merge($checks, $this->checkRelationModelExists());

        return $checks;
    }

    private function checkRelationModelExists()
    {
        $relModel = $this->relation->toModel;
        if ($relModel instanceof Model):
            $relModel = $relModel->meta->modelName;
        endif;

        $relMissing = $this->scopeModel->meta->registry->hasModel($relModel);

        $error = [];

        if (!$relMissing) :
            $msg = "Field defines a relation with model '%s', which is either does not exist, or is abstract.";

            $error = [
                CheckError::createObject(
                    [
                        'message' => sprintf($msg, $relModel),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E300',
                    ]
                ),
            ];
        endif;

        return $error;
    }

    /**
     * Points to the model the field relates to. For example, Author in ForeignKey(['model'=>Author]).
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRelatedModel()
    {
        BaseOrm::getRegistry()->isAppReady();

        return $this->relation->toModel;
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);

        $callback = function ($kwargs) {
            /* @var $field RelatedField */
            /** @var $related Model */
            $related = $kwargs['relatedModel'];
            $field = $kwargs['fromField'];
            $field->relation->toModel = $related;
            $field->doRelatedClass($related, $this->relation);
        };

        Tools::lazyRelatedOperation($callback, $this->scopeModel, $this->relation->toModel, ['fromField' => $this]);
    }

    /**
     * We add some properties to the related model class i.e. the inverse model of the relationship initiated by this
     * field.
     *
     * e.g. we add the inverse field to use to query when starting on the inverse side.
     *
     * @param Model|string     $relatedModel
     * @param ForeignObjectRel $relation
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function contributeToInverseClass(Model $relatedModel, ForeignObjectRel $relation)
    {
        $hasMany = HasManyField::createObject(
            [
                'to' => get_class($this->scopeModel),
                'toField' => $relation->fromField->getName(),
                'fromField' => $this,
            ]
        );

        $relatedModel->addToClass($relation->getAccessorName(), $hasMany);
    }

    /**
     * @param Model $relatedModel
     * @param Model $scopeModel
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function doRelatedClass(Model $relatedModel, ForeignObjectRel $relation)
    {
        $this->contributeToInverseClass($relatedModel, $relation);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        if (ArrayHelper::hasKey($kwargs, 'onDelete')):
            $kwargs['onDelete'] = $this->relation->onDelete;
        endif;

        if (is_string($this->relation->toModel)):
            $kwargs['to'] = $this->relation->toModel;
        else:
            $name = $this->relation->toModel->getFullClassName();
            $kwargs['to'] = ClassHelper::getNameFromNs($name, BaseOrm::getModelsNamespace());
        endif;

        if ($this->relation->parentLink):

            $kwargs['parentLink'] = $this->relation->parentLink;
        endif;

        return $kwargs;
    }

    public function getLookup($name)
    {
        if ($name == 'in'):
            return RelatedIn::class;
        elseif ($name == 'exact'):
            return RelatedExact::class;
        elseif ($name == 'gt'):
            return RelatedGreaterThan::class;
        elseif ($name == 'gte'):
            return RelatedGreaterThanOrEqual::class;
        elseif ($name == 'lt'):
            return RelatedLessThan::class;
        elseif ($name == 'lte'):
            return RelatedLessThanOrEqual::class;
        elseif ($name == 'isnull'):
            return RelatedIsNull::class;
        else:
            throw new TypeError(sprintf('Related Field got invalid lookup: %s', $name));
        endif;
    }

    /**
     * Returns the fields that are used to create the relation.
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @return Field[]
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     */
    public function getRelatedFields()
    {
        if (is_string($this->relation->toModel)):
            throw new ValueError(sprintf('Related model "%s" cannot be resolved', $this->relation->toModel));
        endif;
        // origin of relation

        if ($this->fromField == BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT) :
            $this->fromField = $this;
        elseif (is_string($this->fromField)):
            $this->fromField = $this->scopeModel->meta->getField($this->fromField);
        endif;

        //end point of relation
        if (is_string($this->toField)):
            $this->toField = $this->relation->toModel->meta->getField($this->toField);
        else:
            $this->toField = $this->relation->toModel->meta->primaryKey;
        endif;

        return [$this->fromField, $this->toField];
    }

    /**
     * Fetches only fields that are foreign in a relationship i.e. on the toModel.
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getForeignRelatedFields()
    {
        return isset($this->getRelatedFields()[1]) ? [$this->getRelatedFields()[1]] : [];
    }

    /**
     * @param Model $modelInstance
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getRelatedFilter(Model $modelInstance, $reverse = false)
    {
        /* @var $fromField Field */
        /* @var $toField Field */
        $filter = [];
        if ($reverse === false) :
            list($fromField, $toField) = $this->getRelatedFields();
            $value = $modelInstance->{$fromField->getAttrName()};
            $filter[$toField->getAttrName()] = $value;
        else:

            list($toField, $fromField) = $this->getRelatedFields();
            $value = $modelInstance->{$fromField->getAttrName()};
            $filter[$toField->getName()] = $value;
        endif;

        return $filter;
    }

    public function getForeignRelatedFieldsValues(Model $modelInstance)
    {
        return $this->getInstanceValueForFields($modelInstance, $this->getForeignRelatedFields());
    }

    /**
     * Returns the value of fields provided from the model instance.
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @param Model $modelInstance
     * @param array $fields
     *
     * @return array
     */
    public function getInstanceValueForFields(Model $modelInstance, $fields)
    {
        $values = [];
        /** @var $field Field */
        foreach ($fields as $field) :
            $val = $modelInstance->{$field->getAttrName()};
            if (!$val):
                continue;
            endif;
            $values[] = $val;
        endforeach;

        return $values;
    }

    /**
     * Get path from this field to the related model.
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getPathInfo()
    {
        return [
            [
                'fromMeta' => $this->scopeModel->meta,
                'toMeta' => $this->relation->toModel->meta,
                'targetFields' => $this->getForeignRelatedFields(),
                'joinField' => $this, //field that joins the relationship
                'm2m' => false,
                'direct' => true,
            ],
        ];
    }

    /**
     * Get path from the related model to this field's model.
     *
     * @return array
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getReversePathInfo()
    {
        $meta = $this->relation->toModel->meta;

        return [
            [
                'fromMeta' => $meta,
                'toMeta' => $this->scopeModel->meta,
                'targetFields' => [$meta->primaryKey],
                'joinField' => $this->relation, //field that joins the relationship
                'm2m' => false,
                'direct' => false,
            ],
        ];
    }

    /**
     * Define the name that can be used to identify this related object in a table-spanning query.
     *
     * @return string
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getRelatedQueryName()
    {
        // we check if the queryname/ relatedName is set other use we use the name of the model.
        if ($this->relation->relatedQueryName) :
            $name = $this->relation->relatedQueryName;
        elseif ($this->relation->relatedName):
            $name = $this->relation->relatedName;
        else:
            $name = $this->scopeModel->meta->modelName;
        endif;

        return strtolower($name);
    }

    /**
     * Creates the queryset to retrieve data for the relationship that relates to this field.
     *
     * @param $modelName
     * @param $modelInstance
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function queryset($modelName, $modelInstance)
    {

    }
}
