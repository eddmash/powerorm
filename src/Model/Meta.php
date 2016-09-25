<?php

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\app\Registry;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\field\AutoField;
use Eddmash\PowerOrm\Model\field\Field;
use Eddmash\PowerOrm\Object;

/**
 * Class Meta.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Meta extends Object implements MetaInterface
{
    public static $DEBUG_IGNORE = ['scopeModel', 'registry', 'localManyToMany', 'localFields', 'concreteModel', 'overrides'];

    public static $DEFAULT_NAMES = ['registry', 'verboseName', 'dbTable', 'managed', 'proxy'];

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
     * Does this model have an autofield, will have if primary key was set automatically.
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
     * Holds the parents of the model, this is mostly for multi-inheritance.
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
     * This will hold items that will be overriden in the current meta instance.
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

    public function __construct($overrides = [])
    {
        $this->overrides = $overrides;

        if($this->registry == null):
            $this->registry = BaseOrm::getRegistry();
        endif;
    }

    public static function createObject($params = []) {
        return new static($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        // TODO: Implement getFields() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getConcreteFields()
    {
        // TODO: Implement getConcreteFields() method.
    }

    public function getRelatedObjects()
    {
        // TODO: Implement getRelatedObjects() method.
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($propertyName, $classObject)
    {
        $classObject->{$propertyName} = $this;
        $this->modelName = $this->normalizeKey($classObject->getShortClassName());
        $this->scopeModel = $classObject;

        // override with the configs now.
        foreach (static::$DEFAULT_NAMES as $defaultName) :

            if (array_key_exists($defaultName, $this->overrides)):

                $this->{$defaultName} = $this->overrides[$defaultName];
            endif;
        endforeach;

        if($this->dbTable == null):
            $this->dbTable = $this->_getTableName();
        endif;

        $vName = $this->verboseName;
        $this->verboseName = (empty($vName)) ? ucwords(StringHelper::camelToSpace($this->modelName)) : $vName;
    }

    private function _getFields($opts)
    {
        $forward = $reverse = $include_parents = true;
        $include_hidden = false;
        $seen_models = null;
        extract($opts);
    }

    /**
     * {@inheritdoc}
     */
    public function addField($field)
    {
        if ($field->isRelation && $field->manyToMany):
            $this->localManyToMany[$field->name] = $field;
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
    public function setupPrimaryKey($field)
    {
        if (!$this->primaryKey and $field->primaryKey):
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
    public function prepare($model)
    {
        if (empty($this->primaryKey)):
            if (empty($this->parents)):

                // todo $this->setupPrimaryKey($field);
            else:
                $field = new AutoField(['verboseName' => 'ID', 'primaryKey' => true, 'autoCreated' => true]);
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

    public function __debugInfo()
    {
        $meta = [];
        foreach (get_object_vars($this) as $name => $value) :
            if (in_array($name, static::$DEBUG_IGNORE)):
                $meta[$name] = (!is_subclass_of($value, Object::getFullClassName())) ? '** hidden **' : (string) $value;
                continue;
            endif;
            $meta[$name] = $value;
        endforeach;

        return $meta;
    }

    private function _getTableName() {
        return $this->modelName;
    }
}
