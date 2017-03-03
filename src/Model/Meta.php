<?php

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;

/**
 * Metadata options that can be given to a mode..
 *
 * @since 1.0.0
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

    public static $DEFAULT_NAMES = ['registry', 'verboseName', 'dbTable', 'managed', 'proxy', 'autoCreated'];

    /**
     * Th name of the model this meta holds information for.
     *
     * @var string
     */
    public $modelName;

    public $verboseName;

    public $managed = true;

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
    public $dbTable;

    /**
     * The primary key for the model.
     *
     * @var Field
     */
    public $primaryKey;

    /**
     * Holds the parent of the model, this is mostly for multi-inheritance.
     *
     * @var array
     */
    public $parents = [];

    /**
     * Holds many to many relationship that the model initiated.
     *
     * @var array
     */
    public $localManyToMany = [];

    /**
     * This holds fields that belong to the model, i.e when we create the table to represent the model,
     * this field will be represented on that table.
     *
     * @var array
     */
    public $localFields = [];

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
    public $registry;

    // todo
    public $uniqueTogether = [];

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
    public $_reverseRelationTreeCache = [];

    public function __construct($overrides = [])
    {
        $this->overrides = $overrides;

        if ($this->registry == null):
            $this->registry = BaseOrm::getRegistry();
        endif;
    }

    public static function createObject($params = [])
    {
        return new static($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getFields($includeParents = true)
    {
        return $this->fetchFields(['includeParents' => $includeParents, 'reverse' => false]);
    }

    /**
     * Returns a field instance given a field name. The field can be either a forward or reverse field,
     * unless $manyToMany is specified; if it is, only forward fields will be returned.
     *
     * @param $name
     *
     * @since 1.1.0
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
                    "%s has no field named %s. The App registry isn't ready yet, so if this is an autoCreated ".
                    "related field, it won't  be available yet.",
                    $this->modelName,
                    $name
                )
            );
        endif;

        $reverseFields = $this->getReverseOnlyField();

        if (ArrayHelper::hasKey($reverseFields, $name)):

            return $reverseFields[$name];
        endif;

        // if we get here we didn't get the field.
        throw new FieldDoesNotExist(sprintf('%s has no field named %s', $this->modelName, $name));
    }

    public function getNonM2MForwardFields()
    {
        $forwardFields = [];

        /** @var $field Field */
        foreach ($this->getForwardOnlyField() as $name => $field) :
            if (!$field->manyToMany):
                $forwardFields[$name] = $field;
            endif;
        endforeach;

        return $forwardFields;
    }

    private function getForwardOnlyField()
    {
        return $this->fetchFields(['reverse' => false]);
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
     * @since 1.1.0
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
     * Returns all the localFields that are concrete.
     *
     * @since 1.1.0
     * @deprecated use {@see getConcreteFields}
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getLocalConcreteFields()
    {
        $concrete = [];
        foreach ($this->localFields as $name => $field) :
            $concrete[$name] = $field;
        endforeach;

        return $concrete;
    }

    /**
     *  Returns all related objects pointing to the current model. The related objects can come from a one-to-one,
     * one-to-many, or many-to-many field relation type.
     * As this method is very expensive and is accessed frequently (it looks up every field in a model, in every app),
     * it is computed on first access and then is set as a property on every model.
     *
     * @return Field[]
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getReverseRelatedObjects()
    {
        if (empty($this->_reverseRelationTreeCache)):
            $allRelations = [];
            /* @var $model Model */
            /* @var $field RelatedField */
            $allModels = $this->registry->getModels(true);

            // collect all relation fields for this each model
            foreach ($allModels as $name => $model) :
                // just get the forward fields
                $fields = $model->meta->fetchFields(['includeParents' => false, 'reverse' => false]);

                foreach ($fields as $field) :

                    if ($field->isRelation && $field->getRelatedModel() !== null):

                        $allRelations[$field->relation->toModel->meta->modelName][$field->name] = $field;

                    endif;
                endforeach;

            endforeach;

            // set cache relation to models
            foreach ($allModels as $name => $model) :
                // get fields for each model
                $fields = (isset($allRelations[$name])) ? $allRelations[$name] : [];
                $model->meta->_reverseRelationTreeCache = $fields;
            endforeach;
        endif;

        return $this->_reverseRelationTreeCache;
    }

    /**
     * Add the current object to the passed in object.
     *
     * @param string $propertyName the name map the current object to, in the class object passed in
     * @param Model  $classObject  the object to attach the current object to
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contributeToClass($propertyName, $classObject)
    {
        $classObject->{$propertyName} = $this;

        $this->modelName = $this->getName($classObject->getFullClassName());

        $this->scopeModel = $classObject;

        // override with the configs now.
        foreach (static::$DEFAULT_NAMES as $defaultName) :

            if (ArrayHelper::hasKey($this->overrides, $defaultName)):

                $this->{$defaultName} = $this->overrides[$defaultName];
            endif;
        endforeach;

        if ($this->dbTable == null):
            $this->dbTable = $this->getTableName();
        endif;

        $vName = $this->verboseName;

        $this->verboseName = (empty($vName)) ? ucwords(StringHelper::camelToSpace($this->modelName)) : $vName;
    }

    private function fetchFields($kwargs = [])
    {
        $forward = $reverse = $includeParents = true;
        extract($kwargs);

        $fields = [];
        $seen_models = null;

        if ($reverse):
            /**@var $revField RelatedField*/
            foreach ($this->getReverseRelatedObjects() as $revField) :
                $fields[$revField->relation->fromField->getRelatedQueryName()] = $revField->relation;
            endforeach;

//            $fields = array_merge($fields, $);
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
        if ($field->relation != null && $field->manyToMany):
            $this->localManyToMany[$field->name] = $field;
        elseif ($field->relation != null && $field->inverse):
            $this->inverseFields[$field->name] = $field;
        else:
            $this->localFields[$field->name] = $field;
            $this->setupPrimaryKey($field);
        endif;
    }

    /**
     * Set the primary key field of the model.
     *
     * @param Field $field
     *
     * @since 1.1.0
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare(Model $model)
    {
        if (empty($this->primaryKey)):
            if (!empty($this->parents)):
                $field = current(array_values($this->parents));
                $field->primaryKey = true;
                $this->setupPrimaryKey($field);
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function setupProxy($parent)
    {
        $this->dbTable = $parent->meta->dbTable;
        $this->primaryKey = $parent->meta->primaryKey;
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
        return ClassHelper::getNameFromNs($name, BaseOrm::getModelsNamespace());
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

    private function getTableName()
    {
        return sprintf('%s%s', BaseOrm::getDbPrefix(), str_replace('\\', '_', $this->normalizeKey($this->modelName)));
    }

    public function __toString()
    {
        return sprintf('< %s : %s >', get_class($this), $this->modelName);
    }
}
