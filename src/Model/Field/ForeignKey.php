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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\RelatedObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Delete;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ManyToOneRel;
use Eddmash\PowerOrm\Model\Manager\M2OManager;
use Eddmash\PowerOrm\Model\Model;

class ForeignKey extends RelatedField
{
    public $manyToOne = true;
    public $dbConstraint = true;
    public $dbIndex = true;

    /**
     * {@inheritdoc}
     *
     * @var ManyToOneRel
     */
    public $relation;

    public function __construct($kwargs)
    {
        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && $kwargs['rel'] == null)):
            $kwargs['rel'] = ManyToOneRel::createObject(
                [
                    'fromField' => $this,
                    'to' => ArrayHelper::getValue($kwargs, 'to'),
                    'relatedName' => ArrayHelper::getValue($kwargs, 'relatedName'),
                    'relatedQueryName' => ArrayHelper::getValue($kwargs, 'relatedQueryName'),
                    'toField' => ArrayHelper::getValue($kwargs, 'toField'),
                    'parentLink' => ArrayHelper::getValue($kwargs, 'parentLink'),
                    'onDelete' => ArrayHelper::getValue($kwargs, 'onDelete', Delete::CASCADE),
                ]
            );
        endif;

        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = 'this';

        parent::__construct($kwargs);
    }

    /**
     * Gets the field on the related model that is related to this one.
     *
     * @since 1.1.0
     *
     * @return Field
     *
     * @throws ValueError
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRelatedField()
    {
        $fields = $this->getRelatedFields();

        return $fields[1];
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToInverseClass(Model $relatedModel, ForeignObjectRel $relation)
    {
        parent::contributeToInverseClass($relatedModel, $relation);
        if ($this->relation->fieldName == null):
            $this->relation->fieldName = $relatedModel->meta->primaryKey->getName();
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function dbType(Connection $connection)
    {

        // The database column type of a ForeignKey is the column type
        // of the field to which it points.
        return $this->getRelatedField()->dbType($connection);
    }

    public function getAttrName()
    {
        return sprintf('%s_id', $this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();

        if ($this->dbIndex) :
            unset($kwargs['dbIndex']);
        else:
            $kwargs['dbIndex'] = false;
        endif;
        if ($this->dbConstraint === false) :
            $kwargs['dbConstraint'] = $this->dbConstraint;
        endif;

        return $kwargs;
    }

    public function getReverseRelatedFields()
    {
        list($fromField, $toField) = $this->getRelatedFields();

        return [$toField, $fromField];
    }

    public function getJoinColumns($reverse = false)
    {
        if ($reverse):
            return $this->getReverseRelatedFields();
        endif;

        return $this->getRelatedFields();
    }

    public function getReverseJoinColumns()
    {
        return $this->getJoinColumns(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getColExpression($alias, $outputField = null)
    {
        if (is_null($outputField)):
            $outputField = $this->getRelatedField();
        endif;

        return parent::getColExpression($alias, $outputField);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {
        $result = null;

        try {
            // incase the value has been set
            $result = ArrayHelper::getValue($modelInstance->_fieldCache, $this->getCacheName(), ArrayHelper::STRICT);
        } catch (KeyError $e) {
            $relObj = $this->getLocalRelatedFieldsValues($modelInstance);
            if (empty($relObj)):
                return;
            endif;

            $result = $this->queryset($modelInstance)->get();

            /* @var $fromField RelatedField */
            $fromField = $this->getRelatedFields()[0];
            // cache the value of the model
            $modelInstance->_fieldCache[$fromField->getCacheName()] = $result;

            // if we are dealing with fields that only supports one field e.g. OneToOneField
            // If this is a one-to-one relation, set the reverse accessor cache on
            // the related object to the current instance to avoid an extra SQL
            // query if it's accessed later on.
            if (!is_null($result) && !$this->relation->multiple):
                $result->{$this->relation->getCacheName()} = $modelInstance;
            endif;
        }

        if (is_null($result) && $this->null !== false):
            throw new RelatedObjectDoesNotExist(
                sprintf('%s has no %s.', $this->scopeModel->meta->getNamespacedModelName(), $this->getName())
            );
        endif;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        if ($value !== null && !$value instanceof $this->relation->toModel):
            throw new ValueError(
                sprintf(
                    'Cannot assign "%s": "%s->%s" must be a "%s" instance.',
                    $value,
                    $this->scopeModel->meta->getNamespacedModelName(),
                    $this->getName(),
                    $this->relation->toModel->meta->getNamespacedModelName()
                )
            );
        endif;
        /** @var $fromField RelatedField */

        /** @var $toField RelatedField */

        /* @var $field RelatedField */

        list($fromField, $toField) = $this->getRelatedFields();
        if (is_null($value)):

            // if we have a previosly set related object on for the inverse side of this relationship
            // we need to clear it on that related object to since we will be setting it to null on this side
            $relObj = ArrayHelper::getValue($modelInstance->_fieldCache, $this->getCacheName(), null);

            if ($relObj):
                $relObj->{$this->relation->getCacheName()} = null;
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
        if ($value !== null && !$this->relation->multiple):
            $value->{$this->relation->getCacheName()} = $modelInstance;
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function queryset($modelInstance, $reverse = false)
    {
        if ($reverse) :
            $model = $this->getRelatedModel();
        else:
            $model = $this->scopeModel;
        endif;

        // define BaseM2MQueryset
        if (!class_exists('\Eddmash\PowerOrm\Model\Manager\BaseM2OManager', false)):
            $baseClass = $model::getManagerClass();
            $class = sprintf('namespace Eddmash\PowerOrm\Model\Manager;class BaseM2OManager extends \%s{}', $baseClass);
            eval($class);
        endif;

        $manager = M2OManager::createObject(
            [
                'model' => $model,
                'rel' => $this->relation,
                'instance' => $modelInstance,
                'reverse' => $reverse,
            ]
        );

        return $manager;

    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = ArrayHelper::getValue(
            $kwargs,
            'fieldClass',
            ModelChoiceField::class
        );
        if (!ArrayHelper::hasKey($kwargs, 'queryset')) :
            $model = $this->relation->getToModel();
            $kwargs['queryset'] = $model::objects();
        endif;

        $kwargs['valueField'] = $this->relation->fieldName;

        return parent::formField($kwargs);
    }

}
