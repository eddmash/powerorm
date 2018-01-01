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

use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Checks\CheckError;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\ValidationError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Field\Descriptors\DescriptorInterface;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Lookup\Exact;
use Eddmash\PowerOrm\Model\Lookup\GreaterThan;
use Eddmash\PowerOrm\Model\Lookup\GreaterThanOrEqual;
use Eddmash\PowerOrm\Model\Lookup\IContains;
use Eddmash\PowerOrm\Model\Lookup\IEndsWith;
use Eddmash\PowerOrm\Model\Lookup\In;
use Eddmash\PowerOrm\Model\Lookup\IsNull;
use Eddmash\PowerOrm\Model\Lookup\IStartsWith;
use Eddmash\PowerOrm\Model\Lookup\LessThan;
use Eddmash\PowerOrm\Model\Lookup\LessThanOrEqual;
use Eddmash\PowerOrm\Model\Lookup\Range;
use Eddmash\PowerOrm\Model\Lookup\RegisterLookupTrait;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Expression\Col;

class Field extends DeconstructableObject implements FieldInterface, DescriptorInterface
{
    use RegisterLookupTrait;
    use FormFieldReadyTrait;

    const DEBUG_IGNORE = ['scopeModel', 'relation'];

    const BLANK_CHOICE_DASH = ['' => '---------'];

    /**
     * The model to which this field belongs to.
     *
     * @var Model
     */
    public $scopeModel;

    /**
     * Indicates if this field should be serailized when the scope model is being serailized.
     *
     * @var bool
     */
    public $serialize = true;

    protected $name;

    /**
     * @var DescriptorInterface use to return value of the field
     */
    protected $descriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\BaseDescriptor';

    /** @var DescriptorInterface an instance of the field descriptor */
    public $descriptorInstance;

    /**
     * The column comment. Supported by MySQL, PostgreSQL, Oracle, SQL Server, SQL Anywhere and Drizzle. Defaults to null.
     *
     * @var string
     */
    public $comment;

    /**
     * Indicates if is auto incremented field.
     *
     * @var bool
     */
    public $auto = false;

    /**
     * If True, the orm will store empty values as NULL in the database. Default is False i.e NOT NULL.
     *
     * @var bool
     */
    protected $null = false;

    /**
     * If True, this field must be unique throughout the table.
     *
     * This is enforced at the database level and by model validation.
     *
     * If you try to save a model with a duplicate value in a unique field,
     *
     * This option is valid on all field types except ManyToManyField, OneToOneField, and FileField.
     *
     * Note that when unique is True, you don’t need to specify db_index, because unique implies the creation of an index.
     *
     * @var bool
     */
    public $unique = false;

    /**
     * If True, this field is the primary key for the model.
     *
     * If you don’t specify primary_key=True for any field in your model, Poweroem will automatically add an AutoField
     * to hold the primary key,
     *
     * so you don’t need to set primary_key=True on any of your fields unless you want to override the default
     * primary-key behavior.
     *
     * primary_key=True implies null=False and unique=True. Only one primary key is allowed on an object.
     *
     * The primary key field is read-only. If you change the value of the primary key on an existing object and then
     * save it, a new object will be created alongside the old one.
     *
     * @var bool
     */
    public $primaryKey = false;

    /**
     * The maximum length (in characters) of the field.
     *
     * @var int
     */
    public $maxLength;

    /**
     * If True, this field will be indexed.
     *
     * @var bool
     */
    public $dbIndex = false;

    /**
     * The default value for the field.
     *
     * @var mixed
     */
    public $default = NOT_PROVIDED;

    /**
     * This is the attribute name on the scope model that is going to be bound to the model its going to be used to
     * access this field from the model like normal class attributes.
     *
     * @var string
     */
    protected $attrName;

    /**
     * Human friendly name.
     *
     * @var string
     */
    public $verboseName;

    /**
     * indicates if this field automatically created by the orm. eg
     * in most case the 'id' field of most models is automatically created.
     *
     * @var bool
     */
    public $autoCreated = false;

    /**
     * An array consisting of items to use as choices for this field.
     *
     * If this is given, the default form widget will be a select box with these choices instead of the
     * standard text field.
     *
     * The first element in each array is the actual value to be set on the model, and the second element is the
     * human-readable name.
     *
     * For example:
     * $gender_choices = [
     *      'm'=>'Male',
     *      'f'=>'Female',
     * ]
     *
     * $gender =  PModel::CharField(['max_length'=2, 'choices'=$gender_choices])
     *
     * @var
     */
    public $choices = [];

    /**
     * Extra “help” text to be displayed with the form widget.
     * It’s useful for documentation even if your field isn’t used on a form.
     *
     * Note that this value is not HTML-escaped in automatically-generated forms.
     * This lets you include HTML in helpText if you so desire.
     *
     * For example:
     *  <pre><code>helpText="Please use the following format: <em>YYYY-MM-DD</em>."</code></pre>
     *
     * @var
     */
    public $helpText = '';

    /**
     * The name of the column that this field maps to on the database table represented by the scope model.
     *
     * @var string
     */
    protected $dbColumn;

    /**
     * if this is a relationship field, this hold the Relationship object that this field represents.
     *
     * @var ForeignObjectRel
     */
    public $relation;

    /**
     * Indicates if this field is  relationship field.
     *
     * @var bool
     */
    public $isRelation = false;

    /**
     * Indicates if this is a concrete field that can be represented by a column in the database table.
     *
     * @var bool
     */
    public $concrete;

    /**
     * If True, the field is allowed to be blank on form. Default is False.
     *
     * Note that this is different than null. null is purely database-related,
     *
     * whereas form_blank is validation-related.
     *
     * If a field has form_blank=True, form validation will allow entry of an empty value.
     *
     * If a field has form_blank=False, the field will be required.
     *
     * @var bool
     */
    public $formBlank = false;

    public $oneToMany = false;
    public $oneToOne = false;
    public $manyToMany = false;
    public $manyToOne = false;
    public $inverse = false;

    public function __construct($config = [])
    {
        ClassHelper::setAttributes($this, $config, ['rel' => 'relation']);

        if (null !== $this->relation):

            $this->isRelation = true;
        endif;
    }

    /**
     * @param array $config
     *
     * @return *Field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($config = [])
    {
        return new static($config);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $fieldName
     * @param Model  $modelObject
     *
     * @throws FieldError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        if (!StringHelper::isValidVariableName($fieldName)):
            throw new FieldError(
                sprintf(
                    ' "%s" is not a valid field name on the model "%s" .',
                    $fieldName,
                    $modelObject->getFullClassName()
                )
            );
        endif;

        $this->scopeModel = $modelObject;
        $this->setFromName($fieldName);
        $this->scopeModel->getMeta()->addField($this);
        $this->scopeModel->_fieldCache[$this->getAttrName()] = $this->getDefault();
    }

    /**
     * set some values using the field name passed in. called in contributeToClass.
     *
     * @param $fieldName
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function setFromName($fieldName)
    {
        if (empty($this->name)):
            $this->name = $fieldName;
        endif;
        $this->attrName = $this->getAttrName();
        $this->concrete = false === empty($this->getColumnName());

        if (empty($this->verboseName)):
            $this->verboseName = ucwords(str_replace('_', ' ', $this->name));
        endif;
    }

    /**
     * The name of the database column to use for this field.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getColumnName()
    {
        return (empty($this->dbColumn)) ? $this->getAttrName() : $this->dbColumn;
    }

    /**
     * The attribute in the scope model the points to this field.
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getAttrName()
    {
        return $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns true if this field is primaru key or marked as unique.
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function isUnique()
    {
        return $this->unique || $this->primaryKey;
    }

    public function hasDefault()
    {
        return NOT_PROVIDED !== $this->default;
    }

    public function getDefault()
    {
        if ($this->hasDefault()):
            if (is_callable($this->default)):
                return call_user_func($this->default);
        endif;

        return $this->default;
        endif;

        return '';
    }

    public function checks()
    {
        $checks = [];
        $checks = array_merge($checks, $this->checkFieldName());

        return $checks;
    }

    private function checkFieldName()
    {
        $errors = [];

        if (!StringHelper::isValidVariableName($this->name)):
            $errors = [
                CheckError::createObject(
                    [
                        'message' => sprintf(
                            ' "%s" is not a valid field name on model %s .',
                            $this->name,
                            $this->scopeModel->getFullClassName()
                        ),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E001',
                    ]
                ),
            ];
        endif;

        return $errors;
    }

    public function deepClone()
    {
        $skel = $this->deconstruct();
        $constructorArgs = $skel['constructorArgs'];
        $className = $skel['fullName'];

        /* @var $className Field */
        return $className::createObject($constructorArgs);
    }

    /**
     * {@inheritdoc}
     */
    public function deconstruct()
    {
        $path = static::class;
        $name = $this->getShortClassName();

        if (StringHelper::startsWith(static::class, 'Eddmash\PowerOrm\Model\Field')):
            $path = 'Eddmash\PowerOrm\Model\Model';
        $name = sprintf('Model::%s', $this->getShortClassName());
        endif;

        return [
            'constructorArgs' => $this->getConstructorArgs(),
            'path' => $path,
            'fullName' => static::class,
            'name' => $name,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $constArgs = [];

        $defaults = [
            'primaryKey' => false,
            'maxLength' => null,
            'unique' => false,
            'null' => false,
            'dbIndex' => false,
            'default' => NOT_PROVIDED,
            'dbColumn' => null,
            'autoCreated' => false,
            'helpText' => '',
            'choices' => [],
        ];

        foreach ($defaults as $name => $default) :
            $value = ($this->hasProperty($name)) ? $this->{$name} : $default;

        if ($value != $default):

                $constArgs[$name] = $value;

        endif;
        endforeach;

        return $constArgs;
    }

    /**
     * Returns the database column data type for the Field, taking into account the connection.
     *
     * @param ConnectionInterface $connection
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function dbType(ConnectionInterface $connection)
    {
        return;
    }

    /**
     * Convert the value to a php value.
     *
     * As a general rule, convertToPHPValue() should deal gracefully with any of the following arguments:
     *  - An instance of the correct type.
     *  - A string
     *  - None (if the field allows null=True)
     *
     * @param $value
     *
     * @return mixed
     *
     * @throws ValidationError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function convertToPHPValue($value)
    {
        try {
            return Type::getType($this->dbType(BaseOrm::getDbConnection()))->convertToPHPValue(
                $value,
                BaseOrm::getDbConnection()->getDatabasePlatform()
            );
        } catch (\Exception $exception) {
            throw new ValidationError($exception->getMessage(), 'invalid');
        }
    }

    /**
     * Returns choices with a default blank choices included, for use as SelectField choices for this field.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getChoices($opts = [])
    {
        $include_blank_dash = (array_key_exists('include_blank', $opts)) ? false == $opts['include_blank'] : true;

        $first_choice = [];
        if ($include_blank_dash):
            $first_choice = self::BLANK_CHOICE_DASH;
        endif;

        if (!empty($this->choices)):
            return array_merge($first_choice, $this->choices);
        endif;

        // load from relationships todo
    }

    /**
     * Method called prior to prepareValueForDatabaseSave() to prepare the value before being saved
     * (e.g. for DateField.auto_now).
     *
     * model is the instance this field belongs to and add is whether the instance is being saved to the
     * database for the first time.
     *
     * It should return the value of the appropriate attribute from model for this field.
     *
     * The attribute name is in $this->getAttrName() (this is set up by Field).
     *
     * @param Model $model
     * @param bool  $add   is whether the instance is being saved to the database for the first time
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preSave(Model $model, $add)
    {
        return $model->{$this->getAttrName()};
    }

    /**
     * The method should return data in a format that has been prepared for use as a parameter in a query.
     * ie. in the database.
     *
     * e.g. a date string 12-12-12 is converted into a date object
     *
     * most times it will return the same thing as convertToPHPValue() but depending on the field this might change e.g.
     * FileField convertToPHPValue() will return a File object but this method will return the path to be stored/queried in the db.
     *
     * @param mixed $value the current value of the model’s attribute
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareValue($value)
    {
        return $this->convertToPHPValue($value);
    }

    /**
     * Converts value to a backend-specific value. this will be used also when creating lookups.
     *
     * By default it returns value passed in if prepared=true and prepareValue() if is False.
     *
     * @param mixed               $value
     * @param ConnectionInterface $connection
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function convertToDatabaseValue($value, $connection, $prepared = false)
    {
        if (false === $prepared):
            $value = $this->prepareValue($value);
        endif;

        return Type::getType($this->dbType($connection))->convertToDatabaseValue(
            $value,
            $connection->getDatabasePlatform()
        );
    }

    /**
     * Called when the field value must be saved to the database.
     *
     *
     * @param $value
     * @param $connection
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareValueBeforeSave($value, $connection)
    {
        return $this->convertToDatabaseValue($value, $connection, false);
    }

    /**
     * Converts a value as returned by the database to a PHP object.
     *
     * This method is not used for most built-in fields as they are returned in the correct PHP type,
     * or the orm does the conversion itself.
     *
     * If present for the field subclass, fromDbValue() will be called in all circumstances when the data is loaded
     * from the database, including in aggregates and asArray() calls.
     *
     * @param ConnectionInterface $connection
     * @param $value
     * @param $expression
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fromDbValue(ConnectionInterface $connection, $value, $expression)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function valueFromObject($obj)
    {
        return $obj->{$this->getName()};
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        $class = (!is_object($this->scopeModel)) ? '' : $this->scopeModel->getFullClassName();

        $name = (is_null($this->name)) ? '' : $this->name;

        $fieldName = static::class;
        if (StringHelper::startsWith(
            $fieldName,
            "Eddmash\PowerOrm\Model\Field"
        )):

            $fieldName = $this->getShortClassName();
        endif;
        return sprintf(
            '< %s : %s (%s)>',
            $class,
            $fieldName,
            $name
        );
    }

    /**
     * The name we use to cache the value of this field on a scope model ones it has been fetched from the database.
     *
     * @return mixed
     */
    public function getCacheName()
    {
        return sprintf('_%s_cache', $this->getName());
    }

    /**
     * @param $alias
     * @param Field|ForeignObjectRel $outputField
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @return Col
     */
    public function getColExpression($alias, $outputField = null)
    {
        if (is_null($outputField)):
            $outputField = $this;
        endif;

        if ($alias !== $this->scopeModel->getMeta()->getDbTable() && $outputField->name !== $this->name):
            return Col::createObject($alias, $this, $outputField);
        endif;

        return Col::createObject($alias, $this);
    }

    /**
     * @return mixed
     */
    public function getValue(Model $modelInstance)
    {
        return $this->getDescriptor()->getValue($modelInstance);
    }

    /**
     * @param mixed $value
     */
    public function setValue(Model $modelInstance, $value)
    {
        $this->getDescriptor()->setValue($modelInstance, $value);
    }

    /**
     * @return DescriptorInterface
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDescriptor()
    {
        $descriptor = $this->descriptor;

        if (is_null($this->descriptorInstance)):
            $this->descriptorInstance = new $descriptor($this);
        endif;

        return $this->descriptorInstance;
    }

    /**
     * Returns a list of callbacks to use to covert database results into there equivalent php values.
     *
     * @param $connection
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDbConverters($connection)
    {
        return [[$this, 'fromDbValue']];
    }

    public function __debugInfo()
    {
        $meta = parent::__debugInfo();
        $meta['scopeModel'] = $this->scopeModel->getMeta()->getNamespacedModelName();

        return $meta;
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return $this->null;
    }

    /**
     * @param bool $null
     *
     * @return Field
     */
    public function setNull($null)
    {
        $this->null = $null;

        return $this;
    }

    public function isSerializable()
    {
        return $this->serialize;
    }

    /**
     * Converts the value into string. this is used by the serialization module.
     *
     * @param $value
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function valueToString(Model $model)
    {
        return strval($this->valueFromObject($model));
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}

Field::registerLookup(IContains::class);
Field::registerLookup(In::class);
Field::registerLookup(IEndsWith::class);
Field::registerLookup(IStartsWith::class);
Field::registerLookup(Exact::class);
Field::registerLookup(IsNull::class);
Field::registerLookup(GreaterThan::class);
Field::registerLookup(GreaterThanOrEqual::class);
Field::registerLookup(LessThan::class);
Field::registerLookup(LessThanOrEqual::class);
Field::registerLookup(Range::class);
