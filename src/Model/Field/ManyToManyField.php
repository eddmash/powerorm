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

use Eddmash\PowerOrm\Checks\CheckWarning;
use Eddmash\PowerOrm\Form\Fields\ModelMultipleChoiceField;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Migration\FormatFileContent;
use Eddmash\PowerOrm\Model\Delete;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ManyToManyRel;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

/**
 * Provide a many-to-many relation by using an intermediary model that holds two ForeignKey fields pointed at the two
 * sides of the relation.
 *
 * Unless a ``through`` model was provided, ManyToManyField will use the createManyToManyIntermediaryModel factory
 * to automatically generate the intermediary model.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ManyToManyField extends RelatedField
{
    /**
     * {@inheritdoc}
     */
    public $manyToMany = true;

    public $dbTable;

    /**
     * {@inheritdoc}
     *
     * @var ManyToManyRel
     */
    public $relation;

    public $m2mField;

    public $m2mReverseField;

    private $hasNullKwarg;

    protected $descriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\ManyToManyDescriptor';

    public function __construct($kwargs)
    {
        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && is_null($kwargs['rel']))) {
            $kwargs['rel'] = ManyToManyRel::createObject(
                [
                    'fromField' => $this,
                    'to' => ArrayHelper::getValue($kwargs, 'to'),
                    'relatedName' => ArrayHelper::getValue($kwargs, 'relatedName'),
                    'relatedQueryName' => ArrayHelper::getValue($kwargs, 'relatedQueryName'),
                    'through' => ArrayHelper::getValue($kwargs, 'through'),
                    'throughFields' => ArrayHelper::getValue($kwargs, 'throughFields'),
                    'dbConstraint' => ArrayHelper::getValue($kwargs, 'dbConstraint', true),
                ]
            );
        }

        $this->hasNullKwarg = ArrayHelper::hasKey($kwargs, 'null');

        parent::__construct($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);

        // if through model is set
        if (!is_null($this->relation->through)) {
            $callback = function ($kwargs) {
                /* @var $field RelatedField */
                /** @var $related Model */
                $related = $kwargs['relatedModel'];
                $field = $kwargs['fromField'];

                $field->relation->through = $related;
                $field->doRelatedClass($related, $this->relation);
            };

            Tools::lazyRelatedOperation(
                $callback,
                $this->scopeModel,
                $this->relation->through,
                ['fromField' => $this]
            );
        } else {
            $this->relation->through = $this->createM2MIntermediaryModel(
                $this,
                $this->scopeModel
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToInverseClass(Model $relatedModel, ForeignObjectRel $relation)
    {
        $relName = null;

        if (!$relation->isHidden()) {
            $desc = $this->getDescriptor();
            $desc->setReverse(true);
            $relatedModel->addToClass($relation->getAccessorName(), $desc);
        }

        $this->m2mField = function () use ($relation) {
            return $this->getM2MAttr($relation, 'name');
        };
        $this->m2mReverseField = function () use ($relation) {
            return $this->getM2MReverseAttr($relation, 'name');
        };
    }

    /**
     * Creates an intermediary model.
     *
     * @param ManyToManyField $field
     * @param Model           $model
     *
     * @return Model
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     * @throws \Eddmash\PowerOrm\Exception\FieldError
     * @throws \Eddmash\PowerOrm\Exception\ImproperlyConfigured
     * @throws \Eddmash\PowerOrm\Exception\MethodNotExtendableException
     * @throws \Eddmash\PowerOrm\Exception\OrmException
     * @throws \Eddmash\PowerOrm\Exception\TypeError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createM2MIntermediaryModel($field, $model)
    {
        $modelName = $model->getMeta()->getNSModelName();

        if (is_string($field->relation->toModel)) {
            $toModelName = Tools::resolveRelation(
                $model,
                $field->relation->toModel
            );
            $ref = new \ReflectionClass($toModelName);
            $toModelName = $ref->getShortName();
            $toNamespacedModelName = $ref->getName();
        } else {
            $toModelName = $field->relation->toModel->getMeta()->getModelName();
            $toNamespacedModelName = $field->relation->toModel
                ->getMeta()->getNSModelName();
        }

        $className = sprintf(
            '%s_%s_autogen',
            $model->getMeta()->getModelName(),
            $field->getName()
        );
        $from = strtolower($model->getMeta()->getModelName());
        $to = strtolower($toModelName);
        if ($from == $to) {
            $to = sprintf('to_%s', $to);
            $from = sprintf('from_%s', $from);
        }
        $fields = [
            $from => ForeignKey::createObject(
                [
                    'to' => $modelName,
                    'relatedName' => sprintf('%s+', $className),
                    'dbConstraint' => $field->relation->dbConstraint,
                    'onDelete' => Delete::CASCADE,
                ]
            ),
            $to => ForeignKey::createObject(
                [
                    'to' => $toNamespacedModelName,
                    'relatedName' => sprintf('%s+', $className),
                    'dbConstraint' => $field->relation->dbConstraint,
                    'onDelete' => Delete::CASCADE,
                ]
            ),
        ];

        /* @var $intermediaryObj Model */
        $intermediaryClass = FormatFileContent::modelFileTemplate(
            $model->getMeta()->getModelNamespace(), $className, Model::class);

        $fullname = sprintf("%s\%s", $model->getMeta()->getModelNamespace(), $className);
        if (!class_exists($fullname, false)) {
            eval($intermediaryClass->toString());
        }

        /** @var $obj Model */
        $obj = new $fullname();

        $obj->setupClassInfo(
            $fields,
            [
                'meta' => [
                    'appName' => $model->getMeta()->getAppName(),
                    'dbTable' => $field->getM2MDbTable($model->getMeta()),
                    'verboseName' => sprintf('%s-%s relationship', $from, $to),
                    'uniqueTogether' => [$from, $to],
                    'autoCreated' => true,
                ],
            ]
        );

        return $obj;
    }

    /**
     * provides the m2m table name for this relation.
     *
     * @param Meta $meta
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getM2MDbTable($meta)
    {
        if (null !== $this->relation->through) {
            return $this->relation->through->getMeta()->getDbTable();
        } elseif ($this->dbTable) {
            return $this->dbTable;
        } else {
            // oracle allows identifier of 30 chars max
            return StringHelper::truncate(
                sprintf('%s_%s', $meta->getDbTable(), $this->getName()),
                30
            );
        }
    }

    public function checks()
    {
        $checks = parent::checks();
        $checks = array_merge($checks, $this->checkIgnoredKwargOptions());

        return $checks;
    }

    private function checkIgnoredKwargOptions()
    {
        $warnings = [];
        if ($this->hasNullKwarg) {
            $warnings = [
                CheckWarning::createObject(
                    [
                        'message' => sprintf('null has no effect on ManyToManyField.'),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.W340',
                    ]
                ),
            ];
        }

        return $warnings;
    }

    /***
     * Gets the m2m relationship field on the through model.
     * @param Model $model
     * @param       $attr
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getM2MAttr(ForeignObjectRel $relation, $attr)
    {
        $cache_attr = sprintf('_m2m_%s_cache', $attr);
        if ($this->hasProperty($cache_attr)) {
            return $this->{$cache_attr};
        }

        $linkName = null;
        if ($this->relation->throughFields) {
            $linkName = $this->relation->throughFields[0];
        }

        $fromModel = $relation->getFromModel()->getMeta()->getNSModelName();
        /** @var $field RelatedField */
        foreach ($this->relation->through->getMeta()->getFields() as $field) {
            if ($field->isRelation && (is_null($linkName) || $linkName == $field->getName()) &&
                $field->relation->toModel->getMeta()->getNSModelName() == $fromModel) {
                $this->{$cache_attr} = ('name' == $attr) ? call_user_func([$field, 'getName']) : $field->{$attr};

                return $this->{$cache_attr};
            }
        }
    }

    /***
     * Gets the m2m relationship field on the through model.
     * @param Model $model
     * @param       $attr
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getM2MReverseAttr(ForeignObjectRel $relation, $attr)
    {
        $cache_attr = sprintf('_m2m_reverse_%s_cache', $attr);
        if ($this->hasProperty($cache_attr)) {
            return $this->{$cache_attr};
        }

        $linkName = null;
        if ($this->relation->throughFields) {
            $linkName = $this->relation->throughFields[1];
        }

        $tomodel = $relation->toModel->getMeta()->getNSModelName();
        /** @var $field RelatedField */
        foreach ($this->relation->through->getMeta()->getFields(true) as $field) {
            if ($field->isRelation && (is_null($linkName) || $linkName == $field->getName()) &&
                $field->relation->toModel->getMeta()->getNSModelName() == $tomodel
            ) {
                $this->{$cache_attr} = ('name' == $attr) ? call_user_func([$field, 'getName']) : $field->{$attr};

                return $this->{$cache_attr};
            }
        }
    }

    /**
     * Get path from this field to the related model.
     *
     * @param bool $direct
     *
     * @return array
     *
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @throws \Eddmash\PowerOrm\Exception\ValueError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    private function pathInfo($direct = false)
    {
        $paths = [];
        $throughModel = $this->relation->through;

        // we get the fields on the through model to use when querying from owning to inverse side
        // of the many to many relationship
        // user->role(owning) and role->users_set(inverse)

        /* @var $field RelatedField */
        /* @var $reverseField RelatedField */
        $fromOwningSide = call_user_func($this->m2mField);
        $fromInverseSide = call_user_func($this->m2mReverseField);
        $field = $throughModel->getMeta()->getField($fromOwningSide);
        $reverseField = $throughModel->getMeta()->getField($fromInverseSide);

        if ($direct) {
            $paths = array_merge($paths, $field->getReversePathInfo());
            $paths = array_merge($paths, $reverseField->getForwardPathInfo());
        } else {
            $paths = array_merge($paths, $reverseField->getReversePathInfo());
            $paths = array_merge($paths, $field->getForwardPathInfo());
        }

        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getForwardPathInfo()
    {
        return $this->pathInfo(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getReversePathInfo()
    {
        return $this->pathInfo(false);
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = ArrayHelper::getValue(
            $kwargs,
            'fieldClass',
            ModelMultipleChoiceField::class
        );
        if (!ArrayHelper::hasKey($kwargs, 'queryset')) {
            $model = $this->relation->getToModel();
            $kwargs['queryset'] = $model::objects();
        }

        return parent::formField($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function valueFromObject($obj)
    {
        if (!$obj->pk) {
            return [];
        }

        return $obj->{$this->getName()}->all(); // TODO: Change the autogenerated stub
    }

    /**
     * {@inheritdoc}
     */
    public function saveFromForm(Model $model, $value)
    {
        $model->{$this->getName()}->set($value);
    }

    /**@inheritdoc */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();

        if ($this->relation->through) {
            if (is_string($this->relation->through)) {
                $kwargs['through'] = $this->relation->through;
            } elseif (!$this->relation->through->getMeta()->autoCreated) {
                $kwargs['through'] = sprintf("%s\%s",
                    $this->relation->through->getMeta()->getModelNamespace(),
                    $this->relation->through->getMeta()->getModelName()
                );
            }
        }

        if (!$this->relation->dbConstraint) {
            $kwargs['dbConstraint'] = $this->relation->dbConstraint;
        }

        if ($this->relation->throughFields) {
            $kwargs['throughFields'] = $this->relation->throughFields;
        }

        return $kwargs;
    }
}
