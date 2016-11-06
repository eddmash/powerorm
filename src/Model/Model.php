<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\ArrayObjectInterface;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckError;
use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\OneToOneField;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Base class for all models in the ORM, this class cannot be instantiated on its own.
 * Class Model.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Model extends DeconstructableObject implements ModelInterface, ArrayObjectInterface
{
    const DEBUG_IGNORE = ['_fieldCache'];
    const MODELBASE = '\Eddmash\PowerOrm\Model\Model';

    /**
     * Holds the arguments passed to the constructor.
     *
     * @var array
     */
    protected $constructorArgs;

    /**
     * Holds all the fields tha belong to this model.
     *
     * @var array
     */
    protected $_fieldCache;

    /**
     * Holds the name of the database table that this model represents.
     *
     * To save you time, The ORM automatically derives the name of the database table from the name of your model
     * class.
     *
     * for a user model with a namespaced name of \myapp\models\user, the table name will be user
     *
     * @var string
     * @ignore
     */
    protected $tableName;

    /**
     * Indicates if the orm should managed the table being represented by this model.
     *
     * Defaults to True, meaning Powerorm  will create the appropriate database tables in migrate or as part of
     * migrations and remove them as part of a flush command.
     *
     * That is, Powerorm manages the database tables’ lifecycles.
     *
     * If False, no database table creation or deletion operations will be performed for this model.
     *
     * This is useful if the model represents an existing table or a database view that has been created by some other
     * means.
     *
     * This is the only difference when managed=False.
     *
     * All other aspects of model handling are exactly the same as normal. This includes:
     *  - Adding an automatic primary key field to the model if you don’t declare it.
     *   To avoid confusion for later code readers, it’s recommended to specify all the columns from the database table
     *   you are modeling when using unmanaged models.
     *
     *  - If a model with managed=False contains a ManyToManyField that points to another unmanaged model,
     *    then the intermediate table for the many-to-many join will also not be created.
     *
     *    However, the intermediary table between one managed and one unmanaged model will be created.
     *
     *    If you need to change this default behavior, create the intermediary table as an explicit model
     *   (with managed set as needed) and use the ManyToManyField->through attribute to make the relation
     *   use your custom model.
     *
     * @var
     */
    protected $managed = true;

    /**
     * When using multi-table inheritance, a new database table is created for each subclass of a model.
     *
     * This is usually the desired behavior, since the subclass needs a place to store any additional data fields that
     * are not present on the base class.
     *
     * Sometimes, however, you only want to change the php behavior of a model – perhaps to add a new method.
     *
     * This is what proxy model inheritance is for:
     *
     * creating a proxy for the original model. You can create, delete and update instances of the proxy model and all
     * the data will be saved as if you were using the original (non-proxied) model.
     *
     * The difference is that you can change things like the default model ordering or the default manager in the proxy,
     * without having to alter the original.
     *
     * Proxy models are declared like normal models. You tell Powerorm that it’s a proxy model by setting the proxy
     * attribute of the class to True.
     *
     * @var
     */
    protected $proxy = false;

    /**
     * Human friendly name.
     *
     * @var string
     */
    protected $verboseName;

    /**
     * Indicates if this is a new model, in the sense that it has not been loaded with values from the database like
     * you would when you want to update a database record.
     *
     * @ignore
     *
     * @var bool
     */
    protected $isNew = true;

    /**
     * Meta information about the model.
     *
     * @ignore
     *
     * @var Meta
     */
    public $meta;

    /**
     * Model constructor.
     *
     * @param array $kwargs the kwargs is an associative array of configurations passed to the model,
     *                      this configurations are all optional.
     *
     * The following are valid configurations:
     *  - db - the database connection to use
     *  - config - to
     */
    public function __construct($kwargs = [])
    {
        $this->constructorArgs = $kwargs;

        $this->init();
    }

    public function loadData($record = [])
    {
        foreach ($record as $name => $value) :

            $this->{$name} = $value;

        endforeach;
    }

    public function init($fields = [], $kwargs = [])
    {
        // get meta settings for this model
        $metaSettings = $this->getMetaSettings();

        if (!empty($kwargs)):

            if (ArrayHelper::hasKey($kwargs, 'meta')):
                $metaSettings = $kwargs['meta'];
            endif;
            if (ArrayHelper::hasKey($kwargs, 'registry')):

                $metaSettings['registry'] = $kwargs['registry'];
            endif;
        endif;

        $meta = Meta::createObject($metaSettings);

        $this->addToClass('meta', $meta);

        list($concreteParentName, $immediateParent, $fieldsList) = $this->_getHierarchyMeta();

        $this->setupFields($fields, $fieldsList);

        // proxy model setup
        if ($this->meta->proxy):
            try {
                $concreteParent = $meta->registry->getModel($concreteParentName);
            } catch (LookupError $e) {
                $concreteParent = $concreteParentName::createObject();
            }
            $this->proxySetup($concreteParent);
        else:
            $this->meta->concreteModel = $this;
            // setup for multiple inheritance
            $this->prepareMultiInheritance($immediateParent);
        endif;

        // ensure the model is ready for use.
        $this->prepare();

        // register the model
        $meta->registry->registerModel($this);
    }

    public static function isModelBase($className)
    {
        return in_array($className, [\PModel::getFullClassName(), self::getFullClassName()]);
    }

    /**
     * @param string       $name
     * @param object|mixed $value
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addToClass($name, $value)
    {
        if ($value instanceof ContributorInterface):
            $value->contributeToClass($name, $this);
        else:
            $this->{$name} = $value;
        endif;
    }

    public function setupFields($fields, $hierarchyFields)
    {
        if (empty($fields)):

            $fields = $hierarchyFields[ClassHelper::getNameFromNs($this->getFullClassName(),
                BaseOrm::getModelsNamespace())];

        endif;

        foreach ($fields as $name => $fieldObj) :

            $this->addToClass($name, $fieldObj);

            // cache it
            $this->_fieldCache[$name] = $fieldObj;
        endforeach;
    }

    /**
     * @param Model $concreteParent
     *
     * @throws FieldError
     * @throws TypeError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function proxySetup($concreteParent)
    {
        $this->meta->setupProxy($concreteParent);
        $this->meta->concreteModel = $concreteParent->meta->concreteModel;
    }

    public function prepareMultiInheritance($parentModelName)
    {
        if (!self::isModelBase($parentModelName) && !StringHelper::isEmpty($parentModelName)):
            $name = ClassHelper::getNameFromNs($parentModelName, BaseOrm::getModelsNamespace());
            $attrName = lcfirst(str_replace(' ', '', ucwords(str_replace('\\', ' ', $name))));
            $attrName = sprintf('%sPtr', $attrName);

            if ($this->_fieldCache == null || !ArrayHelper::hasKey($this->_fieldCache, $attrName)):

                $field = OneToOneField::createObject([
                    'to' => ClassHelper::getNameFromNs($parentModelName, BaseOrm::getModelsNamespace()),
                    'onDelete' => Delete::CASCADE,
                    'name' => $attrName,
                    'autoCreated' => true,
                    'parentLink' => true,
                ]);

                $this->addToClass($attrName, $field);
                $this->meta->parents[$name] = $field;
            endif;

        endif;
    }

    public function prepare()
    {
        $this->meta->prepare($this);
    }

    /**
     * Gets information about a model and all its parent.
     *
     * Some fact to check for :
     *  - proxy model should have at least one concrete model.
     *  - proxy model should not extend an abstract class that contains fields.
     *
     * returns the concrete model in the hierarchy and the fields in each of the models in the hierarchy.
     *
     * @param string    $method     the method to invoke
     * @param null      $args       the arguments to pass to the method
     * @param bool|true $fromOldest do we traverse from BaseObject to the child model
     *
     * @return array
     *
     * @throws TypeError
     * @throws FieldError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _getHierarchyMeta($method = 'unboundFields', $args = null, $fromOldest = true)
    {
        $modelNamespace = BaseOrm::getModelsNamespace();
        $isProxy = $this->meta->proxy;
        // start from oldest parent e.g BaseObject to the last child model
        $parents = $this->getParents([\PModel::getFullClassName(), self::getFullClassName()]);
        $parents = array_merge([$this->getFullClassName() => new \ReflectionObject($this)], $parents);
        if ($fromOldest):
            $parents = array_reverse($parents);
        endif;

        $modelFields = [];

        $concreteParent = null;

        $immediateParent = null;
        $previousAbstractParent = null;

        /** @var $reflectionParent \ReflectionClass */
        /* @var $concreteParent \ReflectionClass */
        foreach ($parents as $index => $reflectionParent) :
            $parentName = $reflectionParent->getName();

            $isOnCurrentModel = ($this->getFullClassName() === $parentName);

            if (!$reflectionParent->hasMethod($method) || $reflectionParent->getMethod($method)->isAbstract()):
                continue;
            endif;

            // concrete is a class that can be instantiated.
            // check for at least one concrete parent nearest to the last child model.
            // since we are going downwards we keep updating the concrete variable.
            if (!($isOnCurrentModel && $isProxy) && $reflectionParent->isInstantiable()):

                $concreteParent = $reflectionParent;
            endif;

            // ************ get the fields *******************
            // we need to call the parent version of the method for each class to get its fields
            // this is how we bypass the overriding functionality of php, otherwise if we don't do this the $method will
            // always provide the fields defined in the last child class.
            $parentMethodCall = sprintf('%1$s::%2$s', $parentName, $method);
            if ($args != null):
                if (is_array($args)):
                    $fields = call_user_func_array([$this, $parentMethodCall], $args);
                else:

                    $fields = call_user_func([$this, $parentMethodCall], $args);
                endif;
            else:
                $fields = call_user_func([$this, $parentMethodCall]);
            endif;

            // =========================== Some Validations ========================

            /*
             * confirm fields with same name don't exist on the parents and current model.
             * i choose to through an exception of overriding for consistence sake. that is on the $method no overriding
             * takes place.
             *
             */
            if ($immediateParent != null && $immediateParent != $previousAbstractParent):

                $fieldKeys = array_keys($fields);
                $parentKeys = (isset($modelFields[$immediateParent])) ? array_keys($modelFields[$immediateParent]) : [];
                $commonFields = array_intersect($parentKeys, $fieldKeys);

                if (!empty($commonFields)):
                    throw new FieldError(
                        sprintf('Local field [ %s ] in class "%s" clashes with field of similar name from base class "%s" ',
                            implode(', ', $commonFields), $parentName, $immediateParent));

                endif;

            endif;

            // if the parent is an abstract model and the current model is a proxy model
            // the parent should not have any fields. abstract models cant have fields
            // incases where the child is a proxy model

            if (($isOnCurrentModel && $isProxy) && $immediateParent === $previousAbstractParent):
                $parentFields = $modelFields[$previousAbstractParent];

                if (!empty($parentFields)):
                    throw new TypeError(sprintf('Abstract base class containing model fields not '.
                        "permitted for proxy model '%s'.", $parentName));
                endif;
            endif;

            // ****************** Import fields from Abstract ***************
            // get fields of immediate parent fields if it was an abstract model add them to child model.
            if (!$isProxy && $previousAbstractParent != null):
                $parentFields = (isset($modelFields[$previousAbstractParent])) ? $modelFields[$previousAbstractParent] : [];
                $fields = array_merge($parentFields, $fields);
            endif;

            if ($reflectionParent->isAbstract()):
                $previousAbstractParent = $parentName;
            else:
                $previousAbstractParent = null;
            endif;

            if (!$isOnCurrentModel):

                $immediateParent = ($parentName == $previousAbstractParent) ? '' : $parentName;

            endif;

            $modelFields[ClassHelper::getNameFromNs($parentName, $modelNamespace)] = $fields;

        endforeach;

        if ($isProxy && $concreteParent == null):
            throw new TypeError(sprintf("Proxy model '%s' has no non-abstract".
                ' model base class.', $this->getShortClassName()));
        endif;

        return [
            ($concreteParent == null) ?: ClassHelper::getNameFromNs($concreteParent->getName(), $modelNamespace),
            $immediateParent,
            $modelFields,
        ];
    }

    public function checks()
    {
        $errors = [];
        $errors = array_merge($errors, $this->_checkModels());
        $errors = array_merge($errors, $this->_checkFields());

        return $errors;
    }

    public function _checkModels()
    {
        $error = [];
        if ($this->meta->proxy) :
            if (!empty($this->meta->localFields) || !empty($this->meta->localManyToMany)):
                $error = [
                    CheckError::createObject([
                        'message' => sprintf('Proxy model "%s" contains model fields.', $this->getFullClassName()),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'models.E017',
                    ]),
                ];
            endif;
        endif;

        return $error;
    }

    public function _checkFields()
    {
        $errors = [];

        /** @var $fields Field */
        foreach ($this->meta->localFields as $fields) :
            $errors = array_merge($errors, $fields->checks());
        endforeach;

        /** @var $field ManyToManyField */
        foreach ($this->meta->localManyToMany as $field) :
            $errors = array_merge($errors, $field->checks());
        endforeach;

        return $errors;
    }

    /**
     * All the model fields are set on this model.
     *
     * <pre><code>public function fields(){
     *      $this->username = ORM::CharField(['max_length'=>30]);
     *      $this->first_name = ORM::CharField(['max_length'=>30]);
     *      $this->last_name = ORM::CharField(['max_length'=>30]);
     *      $this->password = ORM::CharField(['max_length'=>255]);
     *      $this->phone_number = ORM::CharField(['max_length'=>30]);
     * }</code></pre>.
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unboundFields()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function prepareMeta($config = [])
    {
        return new Meta($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaSettings()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function deconstruct()
    {
        // TODO: Implement deconstruct() method.
    }

    /**
     * {@inheritdoc}
     */
    public function constructorArgs()
    {
        return $this->constructorArgs;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return ArrayHelper::hasKey($this->_fieldCache, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->_fieldCache[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->_fieldCache[] = $value;
        } else {
            $this->_fieldCache[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->_fieldCache[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_fieldCache);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->_fieldCache);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->_fieldCache);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->_fieldCache = (array) unserialize((string) $serialized);
    }

    public function __get($name)
    {
        return $this->_fieldCache[$name];
    }

    public function __set($name, $value)
    {
        $this->_fieldCache[$name] = $value;
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s object', $this->getFullClassName());
    }

//    public function __debugInfo()
//    {
//        $model = [];
//        foreach (get_object_vars($this) as $name => $value) :
//            if (in_array($name, self::DEBUG_IGNORE)):
//                $meta[$name] = (!is_subclass_of($value, BaseObject::getFullClassName())) ? '** hidden **' : (string) $value;
//                continue;
//            endif;
//            $model[$name] = $value;
//        endforeach;

//        return $model;
//    }

    /**
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function objects()
    {
        return Queryset::createObject(BaseOrm::getDbConnection(), self::createObject());
    }

    /**
     * Saves the current instance. Override this in a subclass if you want to control the saving process.
     *
     * The 'force_insert' and 'force_update' parameters can be used to insist that the "save" must be an SQL
     * insert or update (or equivalent for on-SQL backends), respectively. Normally, they should not be set.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function save($forceInsert = false, $forceUpdate = false, $connection = null, $updateField = null) {

        // Ensure that a model instance without a PK hasn't been assigned to
        // a ForeignKey or OneToOneField on this model. If the field is
        // nullable, allowing the save() would result in silent data loss.

        if ($forceInsert && ($forceInsert || $forceUpdate)):
            throw new ValueError('Cannot force both insert and updating in model saving.');
        endif;
    }

    /**
     * Do an INSERT. If update_pk is defined then this method should return the new pk for the model.
     *
     * @param $fields
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _doInsert($fields) {
        $qb = BaseOrm::getDbConnection()->createQueryBuilder();

        $qb->insert($this->meta->dbTable);

        foreach ($fields as $key => $value) :
            $qb->setValue($key, $qb->createNamedParameter($value));
        endforeach;

        return $qb->execute();

    }

    /**
     * This method will try to update the model. If the model was updated (in the sense that an update query was done
     * and a matching row was found from the DB) the method will return True.
     *
     * @param $records
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _doUpdate($records, $pkValue, $forceUpdate) {
        $filtered = $this->objects()->filter([$this->meta->primaryKey->name => $pkValue]);

        // We can end up here when saving a model in inheritance chain where
        // update_fields doesn't target any field in current model. In that
        // case we just say the update succeeded. Another case ending up here
        // is a model with just PK - in that case check that the PK still
        // exists.
        if(ArrayHelper::isEmpty($records)):
            return $filtered->exists();
        endif;

        if(!$forceUpdate):

            // It may happen that the object is deleted from the DB right after
            // this check, causing the subsequent UPDATE to return zero matching
            // rows. The same result can occur in some rare cases when the
            // database returns zero despite the UPDATE being executed
            // successfully (a row is matched and updated). In order to
            // distinguish these two cases, the object's existence in the
            // database is again checked for if the UPDATE query returns 0.
            if($filtered->exists()):
                return $filtered->_update($records) || $filtered->exists();
            else:
                return false;
            endif;
        endif;

        return $filtered->_update($records);
    }
}
