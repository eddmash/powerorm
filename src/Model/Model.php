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

use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\ArrayObjectInterface;
use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckError;
use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\AppRegistryNotReady;
use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Exception\MethodNotExtendableException;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\Descriptors\DescriptorInterface;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\OneToOneField;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Manager\BaseManager;
use Eddmash\PowerOrm\Model\Query\Queryset;
use Eddmash\PowerOrm\Serializer\SimpleObjectSerializer;
use Eddmash\PowerOrm\Signals\Signal;
use JsonSerializable;
use ReflectionObject;

/**
 * Base class for all models in the ORM, this class cannot be instantiated on its own.
 * Class Models.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Model extends DeconstructableObject implements ModelInterface, ArrayObjectInterface, JsonSerializable
{
    const FAKENAMESPACE = '__Fake__';

    use ModelFieldsTrait;
    use FormReadyModelTrait;

    const SELF = 'this';

    const CASCADE = 'cascade';

    const DEBUG_IGNORE = ['tableName', 'managed', 'verboseNjame', 'isNew', 'proxy'];

    const MODELBASE = '\Eddmash\PowerOrm\Model\Model';

    const META_SETTING_METHOD = 'getMetaSettings';

    public static $managerClass;

    /**
     * Holds all the fields tha belong to this model.
     *
     * @var array
     */
    public $_fieldCache = [];

    public $_nonModelfields = [];

    /**
     * Meta information about the model.
     *
     * @ignore
     *
     * @var Meta
     */
    protected $meta;

    /**
     * Holds the arguments passed to the constructor.
     *
     * @var array
     */
    protected $constructorArgs;

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
     * Indicates if this is a new model, in the sense that it has not been
     * loaded with values from the database like
     * you would when you want to update a database record.
     *
     * @ignore
     *
     * @var bool
     */
    protected $isNew = true;

    public $_prefetchedObjectCaches;

    /**
     * Models constructor.
     *
     * @param array $kwargs the kwargs is an associative array of configurations
     *                      passed to the model,
     *                      this configurations are all optional.
     *
     * The following are valid configurations:
     *  - db - the database connection to use
     *  - config - to
     */
    public function __construct($kwargs = [])
    {
        $this->constructorArgs = $kwargs;
        $this->loadMeta();
        Signal::dispatch(Signal::MODEL_PRE_INIT, $this, $kwargs);
        $this->setFieldValues($kwargs);
    }

    /**
     * @param Registry|null $registry
     */
    private function loadMeta(Registry $registry = null)
    {
        if (is_null($registry)) {
            $registry = BaseOrm::getRegistry(false);
        }

        try {
            $registry->isAppReady();
            $name = Tools::unifyModelName(get_class($this));

            $model = $registry->getModel($name);

            //todo do a deep clone to be sure all is copied or do
            // a by ref assignment ie.
            // instance references the same meta instance
            $this->meta = $model->getMeta();

            $this->_fieldCache = $model->_fieldCache;
        } catch (AppRegistryNotReady $exception) {
        } catch (LookupError $e) {
        }
    }

    /**
     * @return Meta
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return Meta
     *
     * @throws AppRegistryNotReady
     * @throws LookupError
     */
    public static function getClassMeta()
    {
        return BaseOrm::getRegistry()->getModel(static::class)->getMeta();
    }

    /**
     * @param Meta $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    public function setFieldValues($kwargs)
    {
        // we need meta to exists
        if (is_null($this->getMeta()) || empty($kwargs)) {
            return;
        }

        if ($kwargs) {
            // get also related field if we have kwargs
            $fields = $this->getMeta()->getNonM2MForwardFields();
        } else {
            // otherwise get only the concrete fields
            $fields = $this->getMeta()->getConcreteFields();
        }

        /* @var $field Field */
        foreach ($fields as $name => $field) {
            if (!array_key_exists($field->getAttrName(), $kwargs) &&
                is_null($field->getColumnName())) {
                continue;
            }

            $val = null;
            $isRelated = false;
            $relObject = null;
            if ($kwargs) {
                if ($field instanceof RelatedField) {
                    try {
                        $relObject = ArrayHelper::getValue(
                            $kwargs,
                            $field->getName(),
                            ArrayHelper::STRICT
                        );
                        $isRelated = true;
                        // Object instance was passed in, You can
                        // pass in null for related objects if it's allowed.
                        if (is_null($relObject) && $field->isNull()) {
                            $val = null;
                        }
                    } catch (KeyError $e) {
                        try {
                            $val = ArrayHelper::getValue(
                                $kwargs,
                                $field->getAttrName(),
                                ArrayHelper::STRICT
                            );
                        } catch (KeyError $e) {
                            $val = $field->getDefault();
                        }
                    }
                } else {
                    try {
                        $val = ArrayHelper::getValue(
                            $kwargs,
                            $field->getAttrName(),
                            ArrayHelper::STRICT
                        );
                    } catch (KeyError $e) {
                        $val = $field->getDefault();
                    }
                }
            } else {
                $val = $field->getDefault();
            }

            if ($isRelated) {
                $this->{$field->getName()} = $relObject;
            } else {
                $this->{$field->getAttrName()} = $val;
            }
        }
    }

    public static function isModelBase($className)
    {
        $base = trim(self::class, '\\');

        return in_array($className, [$base]);
    }

    /**
     * @param ConnectionInterface $connection
     * @param                     $fieldNames
     * @param                     $values
     *
     * @return static
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function fromDb(ConnectionInterface $connection, $fieldNames, $values)
    {
        $vals = array_combine($fieldNames, $values);

        return new static($vals);
    }

    /**
     * This method is for internal use only and should not be overriden.
     *
     * @ignore
     *
     * @internal
     *
     * @param array $fields
     * @param array $kwargs
     *
     * @throws AppRegistryNotReady
     * @throws FieldError
     * @throws MethodNotExtendableException
     * @throws TypeError
     * @throws \Eddmash\PowerOrm\Exception\ImproperlyConfigured
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function setupClassInfo($fields = [], $kwargs = [])
    {
        if (ArrayHelper::hasKey($kwargs, 'registry')) {
            $registry = $kwargs['registry'];
        } else {
            $registry = BaseOrm::getRegistry(false);
        }

        // if the registry is already ready and an
        // instance of this class has already been registered with the registry
        // there is no need to register again, just re-use the meta
        // information of the already registered instance
        // remember meta only holds information that related to the
        // class as a whole and not the instances.
        if ($registry->hasModel(get_class($this)) && $registry->ready) {
            $this->loadMeta($registry);
        } else {
            // get meta settings for this model

            $metaSettings = Tools::getClassMetaSettings($this);

            if ($kwargs) {
                if (ArrayHelper::hasKey($kwargs, 'meta')) {
                    $metaSettings = array_merge($kwargs['meta'], $metaSettings);
                }

                // we only add registry if it came in as an argument
                // don't add the default registry, meta class takes care of this.
                if (ArrayHelper::hasKey($kwargs, 'registry')) {
                    $metaSettings['registry'] = $kwargs['registry'];
                }
            }

            $meta = Meta::createObject($metaSettings);

            $this->addToClass('meta', $meta);

            /** @var $immediateParent ReflectionObject */
            list(
                $concreteParentName, $immediateParent,
                $classFields, $parentLink
                ) = self::getHierarchyMeta($this);

            $this->setupFields($fields, $classFields);

            // proxy model setup
            /* @var $concreteParent Model */
            if ($this->getMeta()->proxy) {
                $concreteParentName = Tools::unifyModelName($concreteParentName);
                $concreteParent = $this->getMeta()->getRegistry()
                    ->getRegisteredModel($concreteParentName);
                $this->proxySetup($concreteParent);
            } else {
                $this->getMeta()->setConcreteModel($this);

                // setup for multiple inheritance
                if ($parentLink) {
                    $this->getMeta()->setParentLinks($parentLink);
                }
            }

            // ensure the model is ready for use.
            $this->prepare();

            // register the model
            $meta->getRegistry()->registerModel($this);
        }
    }

    /**
     * @param string       $name
     * @param object|mixed $value
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addToClass($name, $value)
    {
        if ($value instanceof ContributorInterface) {
            $value->contributeToClass($name, $this);
        } else {
            $this->{$name} = $value;
        }
    }

    /**
     * Gets information about a model and all its parent.
     *
     * Some fact to check for :
     *  - proxy model should have at least one concrete model.
     *  - proxy model should not extend an abstract class that contains fields.
     *
     * @param Model     $model
     * @param string    $method     the method to invoke to get fields
     * @param null      $args       the arguments to pass to the method
     * @param bool|true $fromOldest do we traverse from BaseObject to the child
     *                              model
     *
     * @return array The concrete parent model in the hierarchy of the $model, this can
     *               be null if
     *               the model does not have any non-abstract parent
     *               e.g Eddmash\PowerOrm\Models\Models
     *               - The immediate Models, this is the actual class the model extends, unlike
     *               concrete model this is never null.
     *               - The fields in that this model contains, this included those added from
     *               abstract parent classes.
     *               - the parentLink field, not null only if this model needs to enforce a
     *               multi-table inheritance, this, variable contains the field to use to
     *               create the relationship between the parent and the child model
     *
     * @throws FieldError
     * @throws MethodNotExtendableException
     * @throws TypeError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws KeyError
     */
    final public static function getHierarchyMeta(
        $model,
        $method = 'unboundFields',
        $args = null,
        $fromOldest = true
    ) {
        $isProxy = $model->getMeta()->proxy;
        $currentModelRef = new \ReflectionObject($model);
        // start from oldest parent e.g BaseObject to the last child model
        $parents = ClassHelper::getParents($model, [self::getFullClassName()]);
        // append the current model to the begining
        $parents = array_merge(
            [$currentModelRef->getName() => $currentModelRef],
            $parents
        );
        if ($fromOldest) {
            $parents = array_reverse($parents);
        }

        $modelFields = [];

        $parentLink = null;
        $concreteParent = '';

        $immediateParent = null;
        $immediateParentObject = null;
        $previousAbstractParent = null;
        $parentIsAbstract = false;
        /** @var $reflectionParent \ReflectionObject */
        /* @var $concreteParent \ReflectionObject */
        foreach ($parents as $index => $reflectionParent) {
            $parentName = $reflectionParent->getName();

            $parentIsAbstract = (
                !is_null($previousAbstractParent) &&
                !is_null($immediateParent) &&
                $previousAbstractParent === $immediateParent
            );

            $isOnCurrentModel = ($model->getFullClassName() === $parentName);

            if (!$reflectionParent->hasMethod($method) ||
                $reflectionParent->getMethod($method)->isAbstract()) {
                continue;
            }

            // concrete is a class that can be instantiated.
            // check for at least one concrete parent nearest to the last child
            // model. since we are going downwards we keep updating the
            // concrete variable.
            // also a concrete parent cannot be a proxy model.
            if (!$isOnCurrentModel && $reflectionParent->isInstantiable()) {
                if ($reflectionParent) {
                    $meta = Tools::getClassMetaSettings(
                        $model,
                        $reflectionParent->getName()
                    );
                    $parentIsProxy = ArrayHelper::getValue(
                        $meta,
                        'proxy',
                        false
                    );
                    if (!$parentIsProxy) {
                        $concreteParent = $reflectionParent;
                    }
                }
            }

            // ************ get the fields *******************

            // we need to call the parent version of the method for each class
            // to get its fields
            // because "$method" is private, this way we don't have any chance of
            // inheriting fields from parent.
            $methodReflection = $reflectionParent->getMethod($method);
            $methName = $methodReflection->getDeclaringClass()->getName();
            if (strtolower($methName) === strtolower($parentName)) {
                //ensure its private to avoid method inheritance
                $parentMethodCall = sprintf(
                    '%1$s::%2$s',
                    $parentName,
                    $method
                );

                $fields = Tools::invokeCallable(
                    [$model, $parentMethodCall],
                    $args
                );
            } else {
                $fields = [];
            }

            // =========================== Some Validations ========================

            // if the parent is an abstract model and the current model is
            // a proxy model the parent should not have any fields. abstract
            // models cant have fields incases where the child is a proxy model

            if (($isOnCurrentModel && $isProxy) && $parentIsAbstract) {
                $parentFields = $modelFields[$previousAbstractParent];

                if (!empty($parentFields)) {
                    throw new TypeError(
                        sprintf(
                            'Abstract base class containing model fields not '.
                            "permitted for proxy model '%s'.",
                            $parentName
                        )
                    );
                }
            }

            // check for field clashes
            if (isset($modelFields[$immediateParent])) {
                $parentFields = $modelFields[$immediateParent];
                $commonFields = array_intersect(
                    array_keys($parentFields),
                    array_keys($fields)
                );

                if (!empty($commonFields)) {
                    throw new FieldError(
                        sprintf(
                            'Local field [ %s ] in class "%s" clashes'.
                            ' with field of similar name from base class "%s" ',
                            implode(', ', $commonFields),
                            $parentName,
                            $immediateParent
                        )
                    );
                }
            }

            // ****************** Import fields from Abstract ***************

            if ($parentIsAbstract) {
                if (isset($modelFields[$immediateParent])) {
                    $parentFields = $modelFields[$immediateParent];
                    $fields = array_merge($parentFields, $fields);
                }
            }

            if ($reflectionParent->isAbstract()) {
                $previousAbstractParent = $parentName;
            } else {
                $previousAbstractParent = null;
            }

            if (!$isOnCurrentModel) {
                $immediateParentObject = $reflectionParent;
                $immediateParent = $parentName;
            }

            $modelFields[$parentName] = $fields;
        }

        if ($isProxy && null == $concreteParent) {
            throw new TypeError(
                sprintf(
                    "Proxy model '%s' has no non-abstract".
                    ' model base class.',
                    $model->getShortClassName()
                )
            );
        }

        // ********************* Multi-table inheritance ******************

        $thisModelFields = $modelFields[get_class($model)];
        // Check if current model fields contain at-least one OneToOneField
        // pointing to the immediate parent if its not abstract
        // we do this for parents that are non-proxy, non-abstract models

        if ($concreteParent) {
            if (!$isProxy && !$currentModelRef->isAbstract()) {
                foreach ($thisModelFields as $field) {
                    if ($field instanceof OneToOneField) {
                        $relModelName = (string) $field->relation->toModel;
                        if ($relModelName == $immediateParent) {
                            $parentLink = $field;
                        }
                    }
                }
                if (is_null($parentLink)) {
                    $attrName = sprintf(
                        '%s_ptr',
                        strtolower($concreteParent->getShortName())
                    );

                    if (array_key_exists($attrName, $thisModelFields)) {
                        throw new FieldError(
                            sprintf(
                                "Auto-generated field '%s' in class".
                                '%s for parent_link to base class %s clashes with '.
                                'declared field of the same name.',
                                $attrName,
                                $currentModelRef->getName(),
                                $concreteParent->getName()
                            )
                        );
                    }
                    $parentLink = self::OneToOneField(
                        [
                            'to' => $concreteParent->getName(),
                            'name' => $attrName,
                            'parentLink' => true,
                            'autoCreated' => true,
                            'onDelete' => Delete::CASCADE,
                        ]
                    );
                    $thisModelFields[$attrName] = $parentLink;
                }
            }
        }

        return [
            (empty($concreteParent)) ? null : $concreteParent->getName(),
            $immediateParentObject,
            $thisModelFields,
            $parentLink,
        ];
    }

    private function setupFields($fields, $classFields)
    {
        if (empty($fields)) {
            $fields = $classFields;
        }

        foreach ($fields as $name => $fieldObj) {
            $this->addToClass($name, $fieldObj);
        }
    }

    /**
     * @param Model $concreteParent
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function proxySetup(self $concreteParent)
    {
        $this->getMeta()->setupProxy($concreteParent);
        $this->getMeta()->setConcreteModel($concreteParent->getMeta()->getConcreteModel());
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\ImproperlyConfigured
     */
    private function prepare()
    {
        $this->getMeta()->prepare($this);
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
    public function prepareMeta($config = [])
    {
        return new Meta($config);
    }

    public function checks()
    {
        $errors = [];
        $errors = array_merge($errors, $this->checkModels());
        $errors = array_merge($errors, $this->checkFields());

        return $errors;
    }

    private function checkModels()
    {
        $error = [];
        if ($this->getMeta()->proxy) {
            if (!empty($this->getMeta()->localFields) ||
                !empty($this->getMeta()->localManyToMany)) {
                $error = [
                    CheckError::createObject(
                        [
                            'message' => sprintf(
                                'Proxy model "%s" contains model fields.',
                                static::class
                            ),
                            'hint' => null,
                            'context' => $this,
                            'id' => 'models.E017',
                        ]
                    ),
                ];
            }
        }

        return $error;
    }

    private function checkFields()
    {
        $errors = [];

        /** @var $fields Field */
        foreach ($this->getMeta()->localFields as $fields) {
            $errors = array_merge($errors, $fields->checks());
        }

        /** @var $field ManyToManyField */
        foreach ($this->getMeta()->localManyToMany as $field) {
            $errors = array_merge($errors, $field->checks());
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function deconstruct()
    {
        // TODO: Implement deconstruct() method.
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        $pk = '';
        if ($this->getMeta()->primaryKey) {
            $attrName = $this->getMeta()->primaryKey->getAttrName();
            try {
                $pk = $this->{$attrName};
            } catch (\Exception $e) {
            }
        }

        return sprintf('%s %s', $this->getShortClassName(), $pk);
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
        throw new TypeError(
            sprintf(
                "TypeError: '%s' object is not iterable",
                static::class
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        throw new TypeError(
            sprintf(
                "TypeError: object of type '%s' is not countable)",
                static::class
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->_fieldCache = (array) unserialize((string) $serialized);
    }

    /**
     * @param $name
     *
     * @return mixed
     *
     * @throws AttributeError
     * @throws KeyError
     *
     * @since  1.1.0
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function __get($name)
    {
        // pk has a special meaning to the orm.
        if ('pk' === $name) {
            $pkName = $this->getMeta()->primaryKey->getAttrName();

            return ArrayHelper::getValue($this->_fieldCache, $pkName);
        }

        try {
            /** @var $field RelatedField */
            $field = $this->getMeta()->getField($name);
            if ($field instanceof ForeignObjectRel) {
                throw new FieldDoesNotExist();
            }

            // did we get a request for the attribute value and not the object
            // this mostly occur on relationship fields e.g. user field returns
            // the related user object whilst user_id returns the actual value
            // the gets put into the database.
            if ($name === $field->getAttrName()) {
                $val = $this->_fieldCache[$name];
                if (!($val instanceof DescriptorInterface)) {
                    return $val;
                }
            }

            return $field->getValue($this);
        } catch (FieldDoesNotExist $e) {
            if (ArrayHelper::hasKey($this->_nonModelfields, $name)) {
                return ArrayHelper::getValue($this->_nonModelfields, $name);
            }
            if (!ArrayHelper::hasKey(get_object_vars($this), $name) &&
                !ArrayHelper::hasKey($this->_fieldCache, $name)) {
                throw new AttributeError(
                    sprintf(
                        "AttributeError: '%s' object has no ".
                        "attribute '%s'. choices are [ %s ]",
                        $this->getMeta()->getNSModelName(),
                        $name,
                        implode(
                            ', ',
                            Tools::getFieldNamesFromMeta($this->getMeta())
                        )
                    )
                );
            }
        }

        if (ArrayHelper::hasKey($this->_fieldCache, $name)) {
            return ArrayHelper::getValue($this->_fieldCache, $name);
        }

        return ArrayHelper::getValue($this->_nonModelfields, $name);
    }

    public function __set($name, $value)
    {
        // pk has a special meaning to the orm.
        if ('pk' === $name) {
            $pkName = $this->getMeta()->primaryKey->getAttrName();
            $this->_fieldCache[$pkName] = $value;

            return;
        }

        /* @var $field RelatedField */
        try {
            $field = $this->getMeta()->getField($name);
            if ($field->getAttrName() !== $field->getName() &&
                $field->getAttrName() === $name) {
                $this->_fieldCache[$name] = $value;
            } else {
                $field->setValue($this, $value);
            }
        } catch (FieldDoesNotExist $e) {
            // we assume this is not a model field being set
            // or its a completely new property we are attaching to
            // the model dynamically
            $this->_nonModelfields[$name] = $value;
        }
    }

    /**
     * Saves the current instance. Override this in a subclass if you want to
     * control the saving process.
     *
     * The 'force_insert' and 'force_update' parameters can be used to insist
     * that the "save" must be an SQL
     * insert or update (or equivalent for on-SQL backends), respectively.
     * Normally, they should not be set.
     *
     * @param bool|false $forceInsert
     * @param bool|false $forceUpdate
     * @param null       $connection
     * @param null       $updateField
     *
     * @throws ValueError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function save(
        $updateFields = null,
        $forceInsert = false,
        $forceUpdate = false,
        $connection = null
    ) {
        // Ensure that a model instance without a PK hasn't been assigned to
        // a ForeignKey or OneToOneField on this model. If the field is
        // nullable, allowing the save() would result in silent data loss.
        /* @var $relObject Model */
        /** @var $field Field */
        foreach ($this->getMeta()->getConcreteFields() as $name => $field) {
            if ($field->isRelation) {
                //If the related field isn't cached, then an instance hasn't
                //been assigned and there's no need to worry about this check.
                if ($this->hasProperty($field->getCacheName()) &&
                    $this->{$field->getCacheName()}) {
                    continue;
                }

                $relObject = $this->{$field->getName()};

                if ($relObject && is_null($relObject->getMeta()->primaryKey)) {
                    throw new ValueError(
                        sprintf(
                            'save() prohibited to prevent data loss '.
                            "due to unsaved related object '%s'.",
                            $field->getName()
                        )
                    );
                }
            }
        }

        if ($forceInsert && ($forceInsert || $forceUpdate)) {
            throw new ValueError(
                'Cannot force both insert and updating'.
                ' in model saving.'
            );
        }

        $deferedFields = $this->getDeferredFields();

        // if we got update_fields, ensure we got fields actually exist on
        // the model
        if ($updateFields) {
            $modelFields = $this->getMeta()->getNonM2MForwardFields();
            $fieldsNames = [];
            /** @var $modelField Field */
            foreach ($modelFields as $name => $modelField) {
                if ($modelField->primaryKey) {
                    continue;
                }
                $fieldsNames[] = $modelField->getName();

                // if attribute name and the field name provided arent the same,
                // add also add the attribute name.e.g. in related fields
                if ($modelField->getName() !== $modelField->getAttrName()) {
                    $fieldsNames[] = $modelField->getAttrName();
                }
            }

            $nonModelFields = array_diff($updateFields, $fieldsNames);
            if ($nonModelFields) {
                throw new ValueError(
                    sprintf(
                        'The following fields do not exist in this '.
                        'model or are m2m fields: %s'.implode(
                            ', ',
                            $nonModelFields
                        )
                    )
                );
            }
        } elseif (!$forceInsert && $deferedFields) {
            // if we have some deferred fields, we need to set the fields to
            // update as the onces that were loaded.
            $concreteFields = $this->getMeta()->getConcreteFields();

            $fieldsNames = [];
            /** @var $concreteField Field */
            foreach ($concreteFields as $name => $concreteField) {
                if (!$concreteField->primaryKey &&
                    !$concreteField->hasProperty('through')) {
                    $fieldsNames[] = $concreteField->getName();
                }
            }
            $loadedFields = array_diff($fieldsNames, $deferedFields);

            if ($loadedFields) {
                $updateFields = $loadedFields;
            }
        }

        $this->prepareSave($updateFields);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($name)
    {
        return parent::hasProperty($name) || array_key_exists(
                $name,
                $this->_fieldCache
            );
    }

    public function getDeferredFields()
    {
        $concreteFields = $this->getMeta()->getConcreteFields();

        $deferedFields = [];
        /** @var $concreteField Field */
        foreach ($concreteFields as $concreteField) {
            if (!array_key_exists(
                $concreteField->getName(),
                $this->_fieldCache
            )) {
                $deferedFields[] = $concreteField->getName();
            }
        }

        return $deferedFields;
    }

    /**
     * Handles the parts of saving which should be done only once per save,
     * yet need to be done in raw saves, too.
     * This includes some sanity checks and signal sending.
     *
     * The 'raw' argument is telling save_base not to save any parent models
     * and not to do any changes to the values
     * before save. This is used by fixture loading.
     *
     * @param bool|false $raw
     * @param bool|false $forceInsert
     * @param bool|false $forceUpdate
     * @param null       $updateFields
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function prepareSave(
        $updateFields = null,
        $forceInsert = false,
        $forceUpdate = false
    ) {
        $model = $this;

        // for proxy models, we use the concreteModel
        if ($model->getMeta()->proxy) {
            $model = $model->getMeta()->getConcreteModel();
        }

        $meta = $model->getMeta();

        // post save signal
        if (!$meta->autoCreated) {
            $this->dispatchSignal('powerorm.model.pre_save', $model);
        }

        $this->saveParent($model, $updateFields);
        $this->saveTable(
            $model,
            $forceInsert,
            $forceUpdate,
            $updateFields
        );

        // presave signal
        if (!$meta->autoCreated) {
            $this->dispatchSignal('powerorm.model.post_save', $model);
        }
    }

    /**
     * Saves all the parents of cls using values from self.
     *
     * @param Model $model
     * @param       $updateFields
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function saveParent($model, $updateFields)
    {
        $meta = $model->getMeta();

        //        foreach ($meta->getParentLinks() as $key => $field) :
        // Make sure the link fields are synced between parent and self.todo

        //        endforeach;
    }

    /**
     * Does the heavy-lifting involved in saving. Updates or inserts
     * the data for a single table.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @param Model $model
     * @param bool  $raw
     * @param bool  $forceInsert
     * @param bool  $forceUpdate
     * @param null  $updateFields
     *
     * @throws ValueError
     */
    private function saveTable(
        $model,
        $forceInsert = false,
        $forceUpdate = false,
        $updateFields = null
    ) {
        $meta = $this->getMeta();

        /** @var $nonPkFields Field[] */
        $nonPkFields = [];

        /** @var $field Field */
        foreach ($meta->getConcreteFields() as $name => $field) {
            if ($field->primaryKey) {
                continue;
            }
            $nonPkFields[$name] = $field;
        }

        // if any fields we passed in use those
        /** @var $nonePkUpdateFields Field[] */
        $nonePkUpdateFields = [];

        if ($updateFields) {
            foreach ($nonPkFields as $nonPkField) {
                if (in_array($nonPkField->getName(), $updateFields)) {
                    $nonePkUpdateFields[$nonPkField->getName()] = $nonPkField;
                } elseif (in_array($nonPkField->getAttrName(), $updateFields)) {
                    $nonePkUpdateFields[$nonPkField->getAttrName()] = $nonPkField;
                }
            }
        } else {
            $nonePkUpdateFields = $nonPkFields;
        }

        // get pk value
        $pkValue = $this->getPkValue($meta);
        $pkSet = (false === empty($pkValue));

        if (!$pkSet && ($forceUpdate || $forceInsert)) {
            throw new ValueError(
                'Cannot force an update in '.
                'save() with no primary key.'
            );
        }

        $updated = false;

        if ($pkSet && !$forceInsert) {
            $values = [];
            foreach ($nonePkUpdateFields as $nonePkUpdateField) {
                $values[] = [
                    $nonePkUpdateField,
                    null, //model should go here
                    $nonePkUpdateField->preSave($this, false),
                ];
            }

            $updated = $this->doUpdate($values, $pkValue, $forceUpdate);
        }

        if (false === $updated) {
            /* @var $field $concreteField */
            $concreteFields = $meta->getConcreteFields();
            $fields = [];
            foreach ($concreteFields as $name => $concreteField) {
                // skip AutoFields their value is auto created by the database.
                if ($concreteField instanceof AutoField) {
                    continue;
                }
                $fields[$name] = $concreteField;
            }

            $updatePk = ($meta->hasAutoField && !$pkSet);

            $result = $this->doInsert($this, $fields, $updatePk);
            if ($updatePk) {
                $this->{$meta->primaryKey->getAttrName()} = $result;
            }
        }
    }

    /**
     * Get the value of the model primary key.
     *
     * @param Meta|null $meta
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getPkValue(Meta $meta = null)
    {
        if (null === $meta) {
            $meta = $this->getMeta();
        }

        return $this->{$meta->primaryKey->getAttrName()};
    }

    /**
     * This method will try to update the model. If the model was updated
     * (in the sense that an update query was done
     * and a matching row was found from the DB) the method will return True.
     *
     * @param $records
     * @param $pkValue
     * @param $forceUpdate
     *
     * @return bool|int
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function doUpdate($records, $pkValue, $forceUpdate)
    {
        $filtered = static::objects()->filter(
            [
                $this->getMeta()->primaryKey->getName() => $pkValue,
            ]
        );

        // We can end up here when saving a model in inheritance chain where
        // update_fields doesn't target any field in current model. In that
        // case we just say the update succeeded. Another case ending up here
        // is a model with just PK - in that case check that the PK still
        // exists.
        if (ArrayHelper::isEmpty($records)) {
            return $filtered->exists();
        }

        if (!$forceUpdate) {
            // It may happen that the object is deleted from the DB right after
            // this check, causing the subsequent UPDATE to return zero matching
            // rows. The same result can occur in some rare cases when the
            // database returns zero despite the UPDATE being executed
            // successfully (a row is matched and updated). In order to
            // distinguish these two cases, the object's existence in the
            // database is again checked for if the UPDATE query returns 0.

            if ($filtered->exists()) {
                return $filtered->_update($records) || $filtered->exists();
            } else {
                return false;
            }
        }

        return $filtered->_update($records);
    }

    /**
     * @param Model|null $modelInstance
     *
     * @return Queryset
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function objects(self $modelInstance = null)
    {
        $manager = static::getManagerClass();
        if (is_null($modelInstance)) {
            $modelInstance = static::createObject();
        }

        return new $manager($modelInstance);
    }

    /**
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function getManagerClass()
    {
        if (is_null(static::$managerClass)) {
            static::$managerClass = BaseManager::class;
        }

        return static::$managerClass;
    }

    /**
     * Do an INSERT. If update_pk is defined then this method should return
     * the new pk for the model.
     *
     * @param Model   $model
     * @param Field[] $fields   fields that are on this model
     * @param         $returnId
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function doInsert($model, $fields, $returnId)
    {
        return $model::objects()->_insert($this, $fields, $returnId);
    }

    /**
     * {@inheritdoc}
     */
    public function unboundFields()
    {
        return [];
    }

    public function __debugInfo()
    {
        $meta = [];
        foreach ($this->_fieldCache as $name => $item) {
            $meta[$name] = $item;
        }
        $meta['__meta__'] = $this->meta;

        return $meta;
    }

    /**
     * Used during save.its usually invoked when saving related fields.
     *
     * @param RelatedField $field
     *
     * @return mixed
     *
     * @throws ValueError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareDatabaseSave(RelatedField $field)
    {
        if ($this->pk) {
            throw new  ValueError(
                'Unsaved model instance %s '.
                'cannot be used in an ORM query.', $this
            );
        }
        $name = $field->relation->getRelatedField()->getAttrName();

        return $this->{$name};
    }

    /**
     * Converts this models into an array.
     * For a field to appear in the resulting array it needs to serializable.
     *
     * @return mixed
     */
    public function toArray()
    {
        $serial = SimpleObjectSerializer::serialize($this);

        return $serial[0]['fields'];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
