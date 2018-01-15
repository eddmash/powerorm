<?php

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\ImproperlyConfigured;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\Inverse\InverseField;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Manager\BaseManager;

/**
 * Metadata options that can be given to a mode..
 *
 * @since  1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Meta extends DeconstructableObject implements MetaInterface
{
    const DEBUG_IGNORE = [
        'scopeModel',
        'registry',
        'concreteModel',
        'overrides',
    ];

    public static $DEFAULT_NAMES = [
        'registry',
        'verboseName',
        'dbTable',
        'managed',
        'proxy',
        'autoCreated',
        'defaultRelatedName',
        'orderBy',
    ];

    public $modelNamespace;
    public $defaultRelatedName;
    protected $orderBy = [];

    /**
     * Th name of the model this meta holds information for.
     *
     * @var string
     */
    private $modelName;

    public $verboseName;

    public $managed = true;
    public $managerClass;

    public $proxy = false;
    /**
     * Does this model have an AutoField, will have if primary key was set automatically.
     *
     * @var bool
     */
    public $hasAutoField = false;

    /**
     * The AutoField.
     *
     * @var Field
     */
    public $autoField;

    /**
     * The name of the table the model represents.
     *
     * @var string
     */
    protected $dbTable;

    /**
     * The primary key for the model.
     *
     * @var Field
     */
    public $primaryKey;

    /**
     * Holds the field that points to a parent of the scope model,
     * this is mostly for multi-inheritance.
     *
     * @var RelatedField
     */
    protected $parents;

    /**
     * Holds many to many relationship that the model initiated.
     *
     * @var ManyToManyField[]
     */
    public $localManyToMany = [];

    /**
     * This holds fields that belong to the model, i.e when we create the table to represent the model,
     * this field will be represented on that table.
     *
     * @var Field[]
     */
    public $localFields = [];

    /**
     * @var InverseField[]
     */
    public $inverseFields = [];

    /**
     * Holds the model that this meta represents, the reason for this is because we have proxy models which
     * don't represent actual tables in the database.
     *
     * @var Model
     */
    public $scopeModel;

    /**
     * Applies to models that are not abstract, at the end of a proxy relationship, if model is not a proxy,
     * the concreteModel is the model itself.
     *
     * @var Model
     */
    public $concreteModel;

    /**
     * Holds the registry the model is attached to.
     *
     * @var Registry
     */
    protected $registry;

    // todo
    public $uniqueTogether = [];

    private $namspacedModelName;

    /**
     * This will hold items that will be overridden in the current meta instance.
     *
     * @var array
     */
    private $overrides = [];

    /**
     * Indicates if model was auto created by the orm e.g. intermediary model for many to many relationship.
     *
     * @var bool
     */
    public $autoCreated = false;

    /**
     * Holds all the fields that point to the scope model coming from other models.
     *
     * @var array
     */
    protected $_reverseRelationTreeCache = [];

    private $appName;

    public function __construct($overrides = [])
    {
        $this->appName = ArrayHelper::getValue($overrides, 'appName');

        $this->overrides = $overrides;

        if (null == $this->registry):
            $this->registry = BaseOrm::getRegistry();
        endif;

        if (null == $this->managerClass):
            $this->managerClass = BaseManager::class;
        endif;
    }

    public static function createObject($params = [])
    {
        return new static($params);
    }

    /**
     * Returns a list of all forward fields on the model and its parents,including ManyToManyFields.
     *
     * @param bool $includeParents
     * @param bool $inverse
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFields($includeParents = true, $inverse = true, $reverse = true)
    {
        return $this->fetchFields(
            [
                'includeParents' => $includeParents,
                'inverse' => $inverse,
                'reverse' => $reverse,
            ]
        );
    }

    /**
     * Returns a field instance given a field name. The field can be either a forward or reverse field,
     * unless $manyToMany is specified; if it is, only forward fields will be returned.
     *
     * @param $name
     *
     * @since  1.1.0
     *
     * @return Field
     *
     * @throws FieldDoesNotExist
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getField($name)
    {
        // first look in the forward fields only
        $fields = $this->getForwardOnlyField();

        if (ArrayHelper::hasKey($fields, $name)):
            return $fields[$name];
        endif;

        // next look at the reverse fields
        if (!$this->registry->ready):
            throw new FieldDoesNotExist(
                sprintf(
                    "%s has no field named %s. The App registry isn't".
                    ' ready yet, so if this is an autoCreated '.
                    "related field, it won't  be available yet.",
                    $this->getNSModelName(),
                    $name
                )
            );
        endif;

        $reverseFields = $this->getReverseOnlyField();

        if (ArrayHelper::hasKey($reverseFields, $name)):

            return $reverseFields[$name];
        endif;

        // if we get here we didn't get the field.
        throw new FieldDoesNotExist(
            sprintf(
                '%s has no field named %s',
                $this->namspacedModelName,
                $name
            )
        );
    }

    /**
     * @return Field[]
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getNonM2MForwardFields()
    {
        $forwardFields = [];

        /** @var $field Field */
        foreach ($this->fetchFields(['reverse' => false, 'inverse' => false]) as $name => $field) :
            if (!$field->manyToMany):
                $forwardFields[$name] = $field;
            endif;
        endforeach;

        return $forwardFields;
    }

    /**
     * Used only when we are trying to get field that belongs to the scope model.
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getForwardOnlyField()
    {
        $fFields = [];
        $fields = $this->fetchFields(['reverse' => false, 'inverse' => false]);
        foreach ($fields as $field) :
            $fFields[$field->getName()] = $field;

            // Due to the way powerorm's internals work, getField() should also
            // be able to fetch a field by attname. In the case of a concrete
            // field with relation, includes the *_id name too
            $fFields[$field->getAttrName()] = $field;
        endforeach;

        return $fFields;
    }

    private function getReverseOnlyField()
    {
        return $this->fetchFields(['forward' => false]);
    }

    /**
     * Returns a list of all concrete fields on the model and its parents.
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getConcreteFields()
    {
        $concreteFields = [];

        /** @var $field Field */
        foreach ($this->getNonM2MForwardFields() as $name => $field) :

            if ($field->concrete):
                $concreteFields[$name] = $field;
            endif;
        endforeach;

        return $concreteFields;
    }

    /**
     *  Returns all related field objects pointing to the current model.
     * The related objects can come from a one-to-one,
     * one-to-many, or many-to-many field relation type.
     * As this method is very expensive and is accessed frequently
     * (it looks up every field in a model, in every app),
     * it is computed on first access and then is set as a property on every model.
     *
     * @return RelatedField[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     */
    public function getReverseRelatedObjects()
    {
        /* @var $model Model */
        /* @var $field RelatedField */
        if (empty($this->_reverseRelationTreeCache)):
            $allRelations = [];

            $allModels = $this->registry->getModels(true);

            // collect all relation fields for this each model
            foreach ($allModels as $name => $model) :

                // just get the forward fields
                $fields = $model->getMeta()->fetchFields(
                    [
                        'includeParents' => false,
                        'inverse' => false,
                        'reverse' => false,
                    ]
                );

                foreach ($fields as $field) :

                    if ($field->isRelation &&
                        $field->getRelatedModel() &&
                        !is_string($field->getRelatedModel())):

                        $concreteModel = $field->relation
                            ->getToModel()
                            ->getMeta()
                            ->concreteModel
                            ->getMeta()->getNSModelName();
                        $allRelations[$concreteModel][] = $field;
                    endif;

                endforeach;

            endforeach;

            // set cache relation to models
            foreach ($allModels as $name => $model) :
                // get fields for each model
                $fields = (isset($allRelations[$name])) ? $allRelations[$name] : [];

                $model->getMeta()->_reverseRelationTreeCache = $fields;
            endforeach;
        endif;

        // we get the model from the registry
        // to ensure we get the same model instance and same meta class for the model.
        return $this->registry->getModel($this->getNSModelName())
                              ->getMeta()->_reverseRelationTreeCache;
    }

    /**
     * Add the current object to the passed in object.
     *
     * @param string $propertyName the name map the current object to, in the class object passed in
     * @param Model  $classObject  the object to attach the current object to
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contributeToClass($propertyName, $classObject)
    {
        //        $classObject-> = $this;
        ClassHelper::setAttributes($classObject, [$propertyName => $this]);

        $modelClass = $this->modelClassInfo($classObject);
        $this->modelName = $modelClass->getShortName();
        $this->modelNamespace = $modelClass->getNamespaceName();
        $this->namspacedModelName = $modelClass->getName();

        $this->scopeModel = $classObject;

        // override with the configs now.
        foreach (static::$DEFAULT_NAMES as $defaultName) :

            if (ArrayHelper::hasKey($this->overrides, $defaultName)):

                $this->{$defaultName} = $this->overrides[$defaultName];
            endif;
        endforeach;

        if (null == $this->getDbTable()):
            $this->setDbTable($this->getTableName());
        endif;

        $vName = $this->verboseName;

        $this->verboseName = (empty($vName)) ? ucwords(StringHelper::camelToSpace($this->modelName)) : $vName;
    }

    /**
     * @param array $kwargs
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function fetchFields($kwargs = [])
    {
        $includeHidden = false;
        $forward = $inverse = $reverse = $includeParents = true;
        extract($kwargs);

        $fields = [];
        $seen_models = null;

        /* @var $revField RelatedField */
        if ($reverse):

            foreach ($this->getReverseRelatedObjects() as $revField) :
                // if we need to include hidden we add all fields
                // otherwise always add non-hidden relationships
                if ($includeHidden || !$revField->relation->isHidden()) :
                    $fields[$revField->relation->getName()] = $revField->relation;
                endif;
            endforeach;

        endif;

        if ($inverse) :
            foreach ($this->inverseFields as $inverseField) :
                $fields[$inverseField->getName()] = $inverseField;
            endforeach;
        endif;

        if ($forward):
            $fields = array_merge($fields, array_merge($this->localFields, $this->localManyToMany));
        endif;

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(Field $field)
    {
        if (null != $field->relation && $field->manyToMany):
            $this->localManyToMany[$field->getName()] = $field;
        elseif (null != $field->relation && $field->inverse):
            $this->inverseFields[$field->getName()] = $field;
        else:
            $this->localFields[$field->getName()] = $field;
            $this->setupPrimaryKey($field);
        endif;
    }

    /**
     * Set the primary key field of the model.
     *
     * @param Field $field
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function setupPrimaryKey(Field $field)
    {
        if (!$this->primaryKey && $field->primaryKey):
            $this->primaryKey = $field;
        endif;
    }

    /**
     * @param Model $model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws ImproperlyConfigured
     */
    public function prepare(Model $model)
    {
        if (empty($this->primaryKey)):
            if (!empty($this->parents)):
                $field = $this->parents;
                $field->primaryKey = true;
                $this->setupPrimaryKey($field);
                if (!$field->relation->parentLink):
                    throw new ImproperlyConfigured(
                        sprintf('Add parentLink=True to %s.', $field)
                    );
                endif;
            else:

                $field = AutoField::createObject(
                    [
                        'verboseName' => 'ID',
                        'primaryKey' => true,
                        'autoCreated' => true,
                    ]
                );

                $model->addToClass('id', $field);
            endif;
        endif;
    }

    /**
     * @param Model $parent
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function setupProxy(Model $parent)
    {
        $this->dbTable = $parent->getMeta()->getDbTable();
        $this->primaryKey = $parent->getMeta()->primaryKey;
    }

    /**
     * Returns all the options to override on the meta object.
     *
     * @return array
     */
    public function getOverrides()
    {
        return $this->overrides;
    }

    public function getName($name)
    {
        $namespace = '';
        if ($this->getApp()):
            $namespace = $this->getApp()->getNamespace();
        endif;

        return ClassHelper::getNameFromNs($name, $namespace);
    }

    public function canMigrate()
    {
        return $this->managed && !$this->proxy;
    }

    /**
     * {@inheritdoc}
     */
    public function deconstruct()
    {
        // TODO: Implement deconstruct() method.
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function getNSModelName()
    {
        if (StringHelper::startsWith(
            $this->namspacedModelName,
            Model::FAKENAMESPACE
        )):
            return Tools::unifyModelName($this->namspacedModelName);
        endif;

        return $this->namspacedModelName;
    }

    public function getModelNamespace()
    {
        return $this->modelNamespace;
    }

    private function getTableName()
    {
        return str_replace(
            '\\',
            '_',
            $this->normalizeKey($this->modelName)
        );
    }

    public function __toString()
    {
        return sprintf('< %s : %s >', get_class($this), $this->modelName);
    }

    private function modelClassInfo(Model $model)
    {
        return new \ReflectionObject($model);
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @return mixed
     *
     * @throws OrmException
     */
    public function getAppName()
    {
        if (empty($this->appName)):
            throw new OrmException('AppName not set');
        endif;

        return $this->appName;
    }

    /**
     * @return AppInterface
     */
    public function getApp()
    {
        try {
            $app = BaseOrm::getInstance()->getComponent($this->getAppName());

            /* @var $app AppInterface */
            return $app;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDbPrefix()
    {
        $prefix = BaseOrm::getDbPrefix();
        if ($this->getApp()):
            $prefix = $this->getApp()->getDbPrefix();
        endif;
        if (!StringHelper::endsWith($prefix, '_')):
            return sprintf('%s_', $prefix);
        endif;

        return $prefix;
    }

    /**
     * @return string
     */
    public function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * @param string $dbTable
     */
    public function setDbTable($dbTable)
    {
        $this->dbTable = sprintf(
            '%s%s',
            $this->getDbPrefix(),
            $dbTable
        );
    }

    /**
     * @param mixed $appName
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    /**
     * @return Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    public function getParentLinks()
    {
        return $this->parents;
    }

    public function setParentLinks(Field $parentLink)
    {
        $this->parents = $parentLink;
    }
}
