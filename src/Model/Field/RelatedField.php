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
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
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
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelatedField extends Field
{
    protected $inverseDescriptor;

    public $dbConstraint = false;

    /**
     * The field on the related object that the relation is to.
     * By default, The Orm uses the primary key of the related object.
     *
     * @var RelatedField
     */
    public $toField;

    /**
     * points to the current field instance.
     *
     * @var RelatedField
     */
    public $fromField;

    /**
     * RelatedField constructor.
     *
     * @param array $kwargs
     *
     * @throws TypeError
     */
    public function __construct($kwargs = [])
    {
        if (!ArrayHelper::hasKey($kwargs, 'to')) {
            throw new TypeError(
                sprintf(
                    "missing 1 required argument: 'to' for %s",
                    static::class
                )
            );
        }
        parent::__construct($kwargs);
    }

    public function checks()
    {
        $checks = parent::checks();
        $checks = array_merge($checks, $this->checkRelationModelExists());
        $checks = array_merge($checks, $this->checkRelatedNameIsValid());
        $checks = array_merge($checks, $this->checkRelatedQueryNameIsValid());
        $checks = array_merge($checks, $this->checkClashes());

        return $checks;
    }

    private function checkRelationModelExists()
    {
        $relModel = $this->relation->toModel;
        $isString = is_string($this->relation->toModel);
        if ($relModel instanceof Model) {
            $relModel = $relModel->getMeta()->getNSModelName();
        }

        $hasModel = $this->scopeModel->getMeta()
            ->getRegistry()
            ->hasModel($relModel);

        $error = [];

        if (!$hasModel && $isString) {
            $msg = "Field defines a relation with model '%s', which does not exist or belongs to".
                " an app that's not registered with the Orm or the model is abstract.";

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
        }

        return $error;
    }

    private function checkRelatedQueryNameIsValid()
    {
        $errors = [];
        $relatedQueryName = $this->relation->relatedQueryName;

        if (StringHelper::endsWith($relatedQueryName, '_')) {
            $errors[] = CheckError::createObject(
                [
                    'message' => sprintf(
                        "Reverse query name '%s' must not end with an underscore.",
                        $relatedQueryName
                    ),
                    'hint' => 'Add or change a relatedName or relatedQueryName '.
                        'argument for this field.',
                    'context' => $this,
                    'id' => 'fields.E308',
                ]
            );
        }
        if (StringHelper::contains($relatedQueryName, BaseLookup::LOOKUP_SEPARATOR)) {
            $errors[] = CheckError::createObject(
                [
                    'message' => sprintf(
                        "Reverse query name '%s' must not contain '%s'.",
                        $relatedQueryName,
                        BaseLookup::LOOKUP_SEPARATOR
                    ),
                    'hint' => 'Add or change a relatedName or relatedQueryName '.
                        'argument for this field.',
                    'context' => $this,
                    'id' => 'fields.E309',
                ]
            );
        }

        return $errors;
    }

    private function checkRelatedNameIsValid()
    {
        $relatedName = $this->relation->relatedName;
        if (empty($relatedName)) {
            return [];
        }
        $isValid = true;
        if (!StringHelper::isValidVariableName($relatedName)) {
            $isValid = false;
        }
        // if its not a valid name and it doesnt end with '+'
        if (!($isValid || StringHelper::endsWith($relatedName, '+'))) {
            $msg = sprintf(
                "The name '%s' is invalid relatedName for field %s.%s",
                $relatedName,
                $this->scopeModel->getMeta()->getNSModelName(),
                $this->getName()
            );

            return [
                CheckError::createObject(
                    [
                        'message' => $msg,
                        'hint' => "Related name must be a valid php identifier or end with a '+'",
                        'context' => $this,
                        'id' => 'fields.E306',
                    ]
                ),
            ];
        }

        return [];
    }

    /**
     * Check accessor and reverse query name clashes.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function checkClashes()
    {
        // Skip if model name is not resolved.
        if (is_string($this->relation->getToModel())) {
            return [];
        }

        $error = [];
        $relMeta = $this->relation->getToModel()->getMeta();
        $relName = $this->relation->getAccessorName();
        $relQueryName = $this->getRelatedQueryName();
        $isHidden = $this->relation->isHidden();
        $fieldName = sprintf(
            '%s.%s',
            $this->scopeModel->getMeta()->getNSModelName(),
            $this->getName()
        );

        foreach ($relMeta->getFields(
            true,
            false
        ) as $clashField) {
            $clashName = sprintf(
                '%s.%s',
                $relMeta->getNSModelName(),
                $clashField->getName()
            );
            if (!$isHidden && $clashField->getName() == $relName) {
                $msg = "Reverse accessor for '%s' clashes with field name '%s'.";
                $hint = sprintf(
                    "Rename field '%s', or add/change a ".
                    'relatedName argument to the definition '.
                    "for field '%s'.",
                    $clashName,
                    $fieldName
                );
                $error[] = CheckError::createObject(
                    [
                        'message' => sprintf($msg, $fieldName, $clashName),
                        'hint' => $hint,
                        'context' => $this,
                        'id' => 'fields.E302',
                    ]
                );
            }

            if ($clashField->getName() === $relQueryName) {
                $msg = "Reverse query name for '%s' clashes with field name '%s'.";

                $hint = sprintf(
                    "Rename field '%s', or add/change a".
                    ' relatedName argument to the '.
                    "definition for field '%s'.",
                    $clashName,
                    $fieldName
                );

                $error[] = CheckError::createObject(
                    [
                        'message' => sprintf($msg, $fieldName, $clashName),
                        'hint' => $hint,
                        'context' => $this,
                        'id' => 'fields.E303',
                    ]
                );
            }
        }

        foreach ($relMeta->getReverseRelatedObjects() as $reverseRelatedObject) {
            if ($reverseRelatedObject->getName() === $this->getName()) {
                continue;
            }
            $clashName = sprintf(
                '%s.%s',
                $reverseRelatedObject->scopeModel
                    ->getMeta()->getNSModelName(),
                $reverseRelatedObject->getName()
            );

            if (!$isHidden &&
                $reverseRelatedObject->relation->getAccessorName() === $relName) {
                $msg = "Reverse accessor for '%s' clashes with".
                    " reverse accessor for '%s'.";
                $hint = 'Add or change a relatedName argument '.
                    "to the definition for '%s' or '%s'.";
                $error[] = CheckError::createObject(
                    [
                        'message' => sprintf($msg, $fieldName, $clashName),
                        'hint' => sprintf($hint, $fieldName, $clashName),
                        'context' => $this,
                        'id' => 'fields.E304',
                    ]
                );
            }

            if ($reverseRelatedObject->relation->getAccessorName() === $relQueryName) {
                $msg = "Reverse query name for '%s' clashes with reverse query name for '%s'.";
                $hint = "Add or change a relatedName argument to the definition for '%s' or '%s'.";
                $error[] = CheckError::createObject(
                    [
                        'message' => sprintf($msg, $fieldName, $clashName),
                        'hint' => sprintf($hint, $fieldName, $clashName),
                        'context' => $this,
                        'id' => 'fields.E305',
                    ]
                );
            }
        }

        return $error;
    }

    /**
     * Points to the model the field relates to.
     * For example, Author in ForeignKey(['model'=>Author]).
     *
     * @return Model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public function getRelatedModel()
    {
        $this->scopeModel->getMeta()->getRegistry()->isAppReady();

        return $this->relation->toModel;
    }

    private function getInverseDescriptor()
    {
        $class = $this->inverseDescriptor;
        assert($class, 'No reverse descriptor provided');

        return new $class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);
        $namespace = str_replace(
            '\\',
            '_',
            rtrim($this->scopeModel->getMeta()->getNSModelName(), '\\')
        );
        if ($this->relation->relatedName) {
            $this->relation->relatedName = sprintf($this->relation->relatedName, $namespace);
        } elseif ($this->scopeModel->getMeta()->defaultRelatedName) {
            $this->relation->relatedName =
                sprintf(
                    $this->scopeModel->getMeta()->defaultRelatedName,
                    $namespace
                );
        }

        if ($this->relation->relatedQueryName) {
            $this->relation->relatedQueryName =
                sprintf($this->relation->relatedQueryName, $namespace);
        }

        $callback = function ($kwargs) {
            /* @var $field RelatedField */
            /** @var $related Model */
            $related = $kwargs['relatedModel'];
            $field = $kwargs['fromField'];
            $field->relation->toModel = $related;
            $field->doRelatedClass($related, $field->relation);
        };

        Tools::lazyRelatedOperation(
            [
                'fromField' => $this,
                'callable' => $callback,
                'scopeModel' => $this->scopeModel,
                'relatedModel' => $this->relation->toModel,
            ]
        );

        // this allows anyone who var_dumps or any form of dump to see this
        // related fields as part of the model
        // attributes, mostly this is for cases where the instance has been
        // instantiated using new cls() and not
        // values for fields have been set
        $this->scopeModel->_valueCache[$this->getName()] = $this->getDescriptor();
    }

    /**
     * We add some properties to the related model class i.e. the inverse model
     * of the relationship initiated by this field.
     *
     * e.g. we add the inverse field to use to query when starting on the
     * inverse side.
     *
     * @param Model|string     $relatedModel
     * @param ForeignObjectRel $relation
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function contributeToInverseClass(
        Model $relatedModel,
        ForeignObjectRel $relation
    ) {
        if (!$this->relation->isHidden()) {
            $inverseAccessor = $this->getInverseDescriptor();
            $relatedModel->getMeta()->getConcreteModel()->addToClass(
                $relation->getAccessorName(),
                $inverseAccessor
            );
        }
    }

    /**
     * @param Model $relatedModel
     * @param Model $scopeModel
     *
     * @since  1.1.0
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
        if (ArrayHelper::hasKey($kwargs, 'onDelete')) {
            $kwargs['onDelete'] = $this->relation->onDelete;
        }

        if (is_string($this->relation->toModel)) {
            $kwargs['to'] = $this->relation->toModel;
        } else {
            $name = $this->relation->toModel->getMeta()->getNSModelName();

            $kwargs['to'] = $name;
        }

        if ($this->relation->parentLink) {
            $kwargs['parentLink'] = $this->relation->parentLink;
        }
        return $kwargs;
    }

    public function getLookup($name)
    {
        if ('in' == $name) {
            return RelatedIn::class;
        } elseif ('exact' == $name) {
            return RelatedExact::class;
        } elseif ('gt' == $name) {
            return RelatedGreaterThan::class;
        } elseif ('gte' == $name) {
            return RelatedGreaterThanOrEqual::class;
        } elseif ('lt' == $name) {
            return RelatedLessThan::class;
        } elseif ('lte' == $name) {
            return RelatedLessThanOrEqual::class;
        } elseif ('isnull' == $name) {
            return RelatedIsNull::class;
        } else {
            throw new TypeError(sprintf('%s got invalid lookup: %s',
                static::getShortClassName(),
                $name));
        }
    }

    /**
     * Returns the fields that are used to create a relation.
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
        if (is_string($this->relation->toModel)) {
            throw new ValueError(
                sprintf(
                    'Related model "%s" cannot be resolved',
                    $this->relation->toModel
                )
            );
        }
        // owning side of a relation

        if (BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT == $this->fromField) {
            $this->fromField = $this;
        } elseif (is_string($this->fromField)) {
            $this->fromField = $this->scopeModel->getMeta()->getField($this->fromField);
        }

        //inverse side of a relation
        if (is_string($this->toField)) {
            $this->toField = $this->relation->toModel->getMeta()->getField($this->toField);
        } else {
            $this->toField = $this->relation->toModel->getMeta()->primaryKey;
        }

        return [$this->fromField, $this->toField];
    }

    /**
     * Fetches only fields that are foreign in a relationship
     * i.e. the fields on the inverse side i.e. on the toModel.
     *
     * @return Field[]
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getForeignRelatedFields()
    {
        return isset($this->getRelatedFields()[1]) ? [$this->getRelatedFields()[1]] : [];
    }

    /**
     * Fetches only fields that are local to a relationship i.e. on the fromModel.
     *
     * @return Field[]
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getLocalRelatedFields()
    {
        $isset = isset($this->getRelatedFields()[0]);

        return $isset ? [$this->getRelatedFields()[0]] : [];
    }

    public function getForeignRelatedFieldsValues(Model $modelInstance)
    {
        return $this->getInstanceValueForFields(
            $modelInstance,
            $this->getForeignRelatedFields()
        );
    }

    public function getLocalRelatedFieldsValues(Model $modelInstance)
    {
        return $this->getInstanceValueForFields(
            $modelInstance,
            $this->getLocalRelatedFields()
        );
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
        foreach ($fields as $field) {
            $val = $modelInstance->{$field->getAttrName()};
            if (!$val) {
                continue;
            }
            $values[] = $val;
        }

        return $values;
    }

    /**
     * Get path from this field to the related model.
     *
     *
     * @return array
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getForwardPathInfo()
    {
        return [
            [
                'fromMeta' => $this->scopeModel->getMeta(),
                'toMeta' => $this->relation->toModel->getMeta(),
                'targetFields' => $this->getForeignRelatedFields(),
                // field that joins the relationship
                'joinField' => $this,
                // is this path info for m2m relationship
                'm2m' => false,
                // true if we moving from owning to inverse side, false if moving from inverse to owning
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
        $meta = $this->scopeModel->getMeta();

        return [
            [
                'fromMeta' => $this->relation->getToModel()->getMeta(),
                'toMeta' => $meta,
                'targetFields' => [$meta->primaryKey],
                'joinField' => $this->relation, //field that joins the relationship
                'm2m' => false,
                'direct' => false,
            ],
        ];
    }

    /**
     * @return array
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     */
    public function getPathInfo()
    {
        return $this->getForwardPathInfo();
    }

    /**
     * Define the name that can be used to identify this related object in a table-spanning query.
     *
     * @return string
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getRelatedQueryName()
    {
        // we check if the queryname/ relatedName is set otherwise use we use the name of the model.
        if ($this->relation->relatedQueryName) {
            $name = $this->relation->relatedQueryName;
        } elseif ($this->relation->relatedName) {
            $name = $this->relation->relatedName;
        } else {
            $name = $this->scopeModel->getMeta()->getModelName();
        }

        return strtolower($name);
    }

    public function getForwardRelatedFilter(Model $model)
    {
        $toField = $this->getRelatedFields()[1];
        $val = $model->{$toField->getAttrName()};
        $lookup = sprintf('%s__%s', $this->getName(), $toField->getName());

        return [$lookup => $val];
    }

    public function getReverseRelatedFilter(Model $model)
    {
    }

    public function __debugInfo()
    {
        $args = parent::__debugInfo();
        $args['rel'] = $this->relation;

        return $args;
    }
}
