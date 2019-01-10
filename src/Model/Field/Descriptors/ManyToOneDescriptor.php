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

use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\RelatedObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Manager\BaseManager;
use Eddmash\PowerOrm\Model\Manager\M2OManager;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\PrefetchInterface;
use Eddmash\PowerOrm\Model\Query\QuerysetInterface;

class ManyToOneDescriptor extends BaseDescriptor implements PrefetchInterface, RelationDescriptor
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

            if (empty($relObj)) {
                $relObj = null;
            } else {
                $result = $this->getManager($modelInstance)->get();

                /* @var $fromField RelatedField */
                $fromField = $this->field->getRelatedFields()[0];
                // cache the value of the model
                $modelInstance->{$fromField->getCacheName()} = $result;

                // if we are dealing with fields that only supports one field
                // e.g. OneToOneField
                // If this is a one-to-one relation, set the reverse accessor
                // cache on
                // the related object to the current instance to avoid an
                // extra SQL
                //query if it's accessed later on.

                if (!is_null($result) && !$this->field->relation->multiple) {
                    $result->{$this->field->relation->getCacheName()} = $modelInstance;
                }
            }
        }
        // if this field does not allow null values
        if (is_null($result) && !$this->field->isNull()) {
            throw new RelatedObjectDoesNotExist(
                sprintf(
                    '%s has no value for %s.',
                    $this->field->scopeModel->getMeta()->getNSModelName(),
                    $this->field->getName()
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        if (null !== $value && !$value instanceof $this->field->relation->toModel) {
            throw new ValueError(
                sprintf(
                    'Cannot assign "%s": "%s->%s" must be a "%s" instance.',
                    $value,
                    $this->field->scopeModel->getMeta()->getNSModelName(),
                    $this->field->getName(),
                    $this->field->relation->toModel->getMeta()->getNSModelName()
                )
            );
        }
        /** @var $fromField RelatedField */

        /** @var $toField RelatedField */

        /* @var $field RelatedField */

        list($fromField, $toField) = $this->field->getRelatedFields();
        if (is_null($value)) {
            // if we have a previosly set related object on for the inverse
            // side of this relationship
            // we need to clear it on that related object to since
            // we will be setting it to null on this side
            $relObj = ArrayHelper::getValue(
                $modelInstance->_valueCache,
                $this->field->getCacheName(),
                null
            );

            if ($relObj) {
                $relObj->{$this->field->relation->getCacheName()} = null;
            }
            // set the attrib value e.g *_id
            $modelInstance->{$fromField->getAttrName()} = null;
        } else {
            // cache the value of the model
            $modelInstance->_valueCache[$fromField->getCacheName()] = $value;

            // set the attrib value e.g *_id
            $modelInstance->{$fromField->getAttrName()} = $value->{$toField->getAttrName()};
        }

        // if we are dealing with fields that only supports one field e.g. OneToOneField
        // If this is a one-to-one relation, set the reverse accessor cache on
        // the related object to the current instance to avoid an extra SQL
        // query if it's accessed later on.
        if (null !== $value && !$this->field->relation->multiple) {
            $value->{$this->field->relation->getCacheName()} = $modelInstance;
        }
    }

    /**
     * @inheritdoc
     */
    public function getManager(Model $modelInstance)
    {
        if ($this->reverse) {
            $model = $this->field->getRelatedModel();
        } else {
            $model = $this->field->scopeModel;
        }

        // define BaseM2MQueryset
        if (!class_exists('\Eddmash\PowerOrm\Model\Manager\BaseM2OManager', false)) {
            $baseClass = $model::getManagerClass();
            $class = sprintf(
                'namespace Eddmash\PowerOrm\Model\Manager;class BaseM2OManager extends \%s{}',
                $baseClass
            );
            eval($class);
        }

        $managerClass = $this->getManagerClass();
        $manager = new $managerClass(
            [
                'model' => $model,
                'rel' => $this->field->relation,
                'instance' => $modelInstance,
                'reverse' => $this->reverse,
            ]
        );

        return $manager;
    }


    public function getManagerClass(): string
    {
        return M2OManager::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array
    {
        // todo implement
        throw new NotImplemented();
    }

    public function isCached(Model $instance)
    {
        return $instance->{$this->field->getCacheName()};
    }
}
