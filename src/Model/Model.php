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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckError;
use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\Exceptions\FieldError;
use Eddmash\PowerOrm\Exceptions\TypeError;
use Eddmash\PowerOrm\Object;

/**
 * Base class for all models in the ORM, this class cannot be instantiated on its own.
 * Class Model.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Model extends Object implements ModelInterface, \ArrayAccess, \IteratorAggregate, \Countable, \Serializable
{
    const DEBUG_IGNORE = ['_fieldCache'];

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

    public function init($registry = null, $fields = [])
    {
        // ---- set meta
        if (null === $registry):
            // add model to the registry
            $registry = BaseOrm::getRegistry();
        endif;

        // create meta for this model
        $configs = $this->getMeta();
        $configs['registry'] = $registry;
        $configs['dbTable'] = $this->getTableName();

        $meta = new Meta($configs);

        $this->addToClass('meta', $meta);

        $this->setupFields($fields);

        // proxy model setup
        if ($this->meta->proxy):
            $this->proxySetup();
        else:
            $this->meta->concreteModel = $this;

            // setup for multiple inheritance
            $this->prepareMultiInheritance();
        endif;

        // ensure the model is ready for use.
        $this->prepare();

        // register the model
        $registry->registerModel($this);
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

    public function setupFields($fields = [])
    {
        if (empty($fields)):
            $fieldsList = $this->getFieldsFromHierarchy();

            $fields = $fieldsList[$this->getFullClassName()];
        endif;

        foreach ($fields as $name => $fieldObj) :

            $this->addToClass($name, $fieldObj);

            // cache it
            $this->_fieldCache[$name] = $fieldObj;
        endforeach;
    }

    public function proxySetup()
    {
        $concreteParent = reset($this->getHierarchyMeta());

        if ($concreteParent == null):
            throw new TypeError(sprintf("Proxy model '%s' has no non-abstract".
                ' model base class.', $this->getShortClassName()));
        endif;

        $this->meta->setupProxy($concreteParent);
        $this->meta->concreteModel = $concreteParent->meta->concreteModel;
    }

    public function prepareMultiInheritance()
    {
        //todo
    }

    public function prepare()
    {
        $this->meta->prepare($this);
    }

    /**
     * Gets information about a model and all its parent.
     *
     * returns the concrete model in the hierarchy and the fields in each of the models in the hierarchy.
     *
     * @param string    $method     the method to invoke
     * @param null      $args       the arguments to pass to the method
     * @param bool|true $fromOldest do we traverse from Object to the child model
     *
     * @return array
     *
     * @throws TypeError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getHierarchyMeta($method = 'unboundFields', $args = null, $fromOldest = true)
    {
        // start from oldest parent e.g Object to the last child model
        $parents = $this->getParents();
        $parents = array_merge([$this->getFullClassName() => new \ReflectionObject($this)], $parents);
        if ($fromOldest):
            $parents = array_reverse($parents);
        endif;

        $modelFields = [];

        $concreteParent = null;
        foreach ($parents as $reflectionParent) :
            $parent = $reflectionParent->getName();

            if (!$reflectionParent->hasMethod($method)):
                continue;
            endif;

            $reflectionMethod = $reflectionParent->getMethod($method);
            if ($reflectionMethod->isAbstract()):
                continue;
            endif;

            // check for at least once concrete parent nearest to the last child model.
            // since we are going downwards we keep updating the concrete varaible.
            if ($reflectionParent->isInstantiable()):
                $concreteParent = $reflectionParent;
            endif;

            $parentMethodCall = sprintf('%1$s::%2$s', $parent, $method);
            if ($args != null):
                if (is_array($args)):
                    $fields = call_user_func_array([$this, $parentMethodCall], $args);
                else:

                    $fields = call_user_func([$this, $parentMethodCall], $args);
                endif;
            else:
                $fields = call_user_func([$this, $parentMethodCall]);
            endif;

            $modelFields[$parent] = $fields;

        endforeach;

        if ($concreteParent == null):
            throw new TypeError(sprintf("Proxy model '%s' has no non-abstract".
                ' model base class.', $this->getShortClassName()));
        endif;

        return [$concreteParent, $modelFields];
    }

    public function getFieldsFromHierarchy()
    {

        // get hierarchy meta information
        $mashedHierarchyFields = end($this->getHierarchyMeta());

        $hierarchyFields = [];
        $seenFields = [];

        if (empty($fields)):
            foreach ($mashedHierarchyFields as $modelName => $mFields) :
                // does model have any fields ?
                if (empty($mFields)):
                    $hierarchyFields[$modelName] = [];
                    continue;
                endif;

                // if it has fields
                foreach ($mFields as $fieldName => $fieldObj) :

                    // does model declare same more than one field with similar name.
                    if (isset($hierarchyFields[$modelName][$fieldName])):
                        throw new FieldError(
                            sprintf('Field %s is declared more than once on the model %s', $fieldName, $modelName));
                    endif;

                    // have we seen this field name in the models parent?
                    if (in_array($fieldName, array_keys($seenFields))):
                        // has the model been added to the field hierarchy?
                        if (!isset($hierarchyFields[$modelName])):
                            $hierarchyFields[$modelName] = [];
                        endif;
                        continue;
                    endif;

                    // add it to the hierarchy
                    $hierarchyFields[$modelName][$fieldName] = $fieldObj;

                    // mark the field as seen
                    $seenFields[$fieldName] = $modelName;
                endforeach;

            endforeach;

        endif;

        return $hierarchyFields;
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
        if ($this->meta->proxy):
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
        foreach ($this->meta->localFields as $fields) :
            $errors = array_merge($errors, $fields->checks());
        endforeach;

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function unboundFields()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        if (empty($this->tableName)):
            $this->tableName = $this->normalizeKey($this->getShortClassName());
        endif;

        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryset($opts)
    {
        return null;
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
    public function getMeta()
    {
        return [
            'proxy' => $this->proxy,
            'managed' => $this->managed,
            'verboseName' => $this->verboseName,
        ];
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
        return array_key_exists($offset, $this->_fieldCache);
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

    public function __debugInfo()
    {
        $model = [];
        foreach (get_object_vars($this) as $name => $value) :
            if (in_array($name, self::DEBUG_IGNORE)):
                $meta[$name] = (!is_subclass_of($value, Object::getFullClassName())) ? '** hidden **' : (string) $value;
                continue;
            endif;
            $model[$name] = $value;
        endforeach;

        return $model;
    }
}
