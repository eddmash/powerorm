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

use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\RelatedObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Manager\BaseManager;
use Eddmash\PowerOrm\Model\Manager\M2OManager;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

class ManyToOneDescriptor extends BaseDescriptor
{
    /** @var RelatedField */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {
        $result = null;

        try {
            // incase the value has been set
            $result = $modelInstance->{$this->field->getCacheName()};
        } catch (AttributeError $e) {

            $relObj = $this->field->getLocalRelatedFieldsValues($modelInstance);

            if (empty($relObj)):
                $relObj = null;
            else:

                $result = $this->getManager($modelInstance)->get();

                /* @var $fromField RelatedField */
                $fromField = $this->field->getRelatedFields()[0];
                // cache the value of the model
                $modelInstance->{$fromField->getCacheName()} = $result;

                // if we are dealing with fields that only supports one field e.g. OneToOneField
                // If this is a one-to-one relation, set the reverse accessor cache on
                // the related object to the current instance to avoid an extra SQL
                // query if it's accessed later on.

                if (!is_null($result) && !$this->field->relation->multiple):
                    $result->{$this->field->relation->getCacheName()} = $modelInstance;
                endif;
            endif;
        }
        // if this field does not allow null values
        if (is_null($result) && !$this->field->isNull()):
            throw new RelatedObjectDoesNotExist(
                sprintf(
                    '%s has no %s.',
                    $this->field->scopeModel->meta->getNamespacedModelName(),
                    $this->field->getName()
                )
            );
        endif;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        if ($value !== null && !$value instanceof $this->field->relation->toModel):
            throw new ValueError(
                sprintf(
                    'Cannot assign "%s": "%s->%s" must be a "%s" instance.',
                    $value,
                    $this->field->scopeModel->meta->getNamespacedModelName(),
                    $this->field->getName(),
                    $this->field->relation->toModel->meta->getNamespacedModelName()
                )
            );
        endif;
        /** @var $fromField RelatedField */

        /** @var $toField RelatedField */

        /* @var $field RelatedField */

        list($fromField, $toField) = $this->field->getRelatedFields();
        if (is_null($value)):
            // if we have a previosly set related object on for the inverse side of this relationship
            // we need to clear it on that related object to since we will be setting it to null on this side
            $relObj = ArrayHelper::getValue($modelInstance->_fieldCache, $this->field->getCacheName(), null);

            if ($relObj):
                $relObj->{$this->field->relation->getCacheName()} = null;
            endif;
            // set the attrib value e.g *_id
            $modelInstance->{$fromField->getAttrName()} = null;
        else:
            // cache the value of the model
            $modelInstance->_fieldCache[$fromField->getCacheName()] = $value;

            // set the attrib value e.g *_id
            $modelInstance->{$fromField->getAttrName()} = $value->{$toField->getAttrName()};
        endif;

        // if we are dealing with fields that only supports one field e.g. OneToOneField
        // If this is a one-to-one relation, set the reverse accessor cache on
        // the related object to the current instance to avoid an extra SQL
        // query if it's accessed later on.
        if ($value !== null && !$this->field->relation->multiple):
            $value->{$this->field->relation->getCacheName()} = $modelInstance;
        endif;
    }

    /**
     * Creates the queryset to retrieve data for the relationship that relates to this field.
     *
     * @param $modelInstance
     * @param bool $reverse
     *
     * @internal param $modelName
     *
     * @return BaseManager
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getManager($modelInstance, $reverse = false)
    {
        if ($reverse) :
            $model = $this->field->getRelatedModel();
        else:
            $model = $this->field->scopeModel;
        endif;

        // define BaseM2MQueryset
        if (!class_exists('\Eddmash\PowerOrm\Model\Manager\BaseM2OManager', false)):
            $baseClass = $model::getManagerClass();
            $class = sprintf('namespace Eddmash\PowerOrm\Model\Manager;class BaseM2OManager extends \%s{}',
                $baseClass);
            eval($class);
        endif;

        $manager = M2OManager::createObject(
            [
                'model' => $model,
                'rel' => $this->field->relation,
                'instance' => $modelInstance,
                'reverse' => $reverse,
            ]
        );

        return $manager;

    }
}
