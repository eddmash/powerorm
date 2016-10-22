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
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

class Field extends DeconstructableObject implements FieldInterface
{
    const DEBUG_IGNORE = ['scopeModel', 'relation'];

    public $name;

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
    public $null = false;

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
     * The model to which this field belongs to.
     *
     * @var Model
     */
    public $scopeModel;

    public $oneToMany = false;
    public $oneToOne = false;
    public $manyToMany = false;
    public $manyToOne = false;

    private $constructorArgs;

    public function __construct($config = [])
    {
        BaseOrm::configure($this, $config, ['rel' => 'relation']);

        if ($this->relation !== null):
            $this->isRelation = true;
        endif;
    }

    /**
     * @param array $config
     *
     * @return Field
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
                sprintf(' "%s" is not a valid field name on model "%s" .', $fieldName,
                    $modelObject->getFullClassName()));
        endif;

        $this->setFromName($fieldName);
        $this->scopeModel = $modelObject;
        $modelObject->meta->addField($this);
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
        $this->concrete = empty($this->getColumnName());

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
        return $this->default !== NOT_PROVIDED;
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
        $errors = [];
        $errors = array_merge($errors, $this->_checkFieldName());

        return $errors;
    }

    public function _checkFieldName()
    {
        $errors = [];

        if (!StringHelper::isValidVariableName($this->name)):
            $errors = [
                CheckError::createObject([
                    'message' => sprintf(' "%s" is not a valid field name on model %s .',
                        $this->name, $this->scopeModel->getFullClassName()),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E001',
                ]),
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
        $path = '';
        $alias = 'modelField';
        $relAlias = 'relModelField';

        if (StringHelper::startsWith($this->getFullClassName(), 'Eddmash\PowerOrm\Model\Field')):
            $path = sprintf('Eddmash\PowerOrm\Model\Field as %s', $alias);
        endif;
        if (StringHelper::startsWith($this->getFullClassName(), 'Eddmash\PowerOrm\Model\Field\Related')):
            $alias = $relAlias;
            $path = sprintf('Eddmash\PowerOrm\Model\Field\Related as %s', $relAlias);
        endif;

        return [
            'constructorArgs' => $this->getConstructorArgs(),
            'path' => $path,
            'fullName' => $this->getFullClassName(),
            'name' => sprintf('%s\%s', $alias, $this->getShortClassName()),
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
     * {@inheritdoc}
     */
    public function dbType($connection)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function toPhp($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        // TODO: Implement formField() method.
    }

    /**
     * {@inheritdoc}
     */
    public function preSave($model, $add)
    {
        // TODO: Implement preSave() method.
    }

    /**
     * {@inheritdoc}
     */
    public function prepareValue($value)
    {
        // TODO: Implement prepareValue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function prepareValueForDb($value, $connection, $prepared = false)
    {
        // TODO: Implement prepareValueForDb() method.
    }

    /**
     * {@inheritdoc}
     */
    public function prepareValueBeforeSave($value, $connection)
    {
        // TODO: Implement prepareValueBeforeSave() method.
    }

    /**
     * {@inheritdoc}
     */
    public function fromDbValue()
    {
        // TODO: Implement fromDbValue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function valueFromObject($obj)
    {
        return $obj->{$this->attrName};
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return $this->scopeModel->getFullClassName().'->'.$this->name;
    }

    public function __debugInfo()
    {
        $field = [];
        foreach (get_object_vars($this) as $name => $value) :
            if (in_array($name, self::DEBUG_IGNORE)):
                $meta[$name] = (is_subclass_of($value, Object::getFullClassName())) ?: '** hidden **';
                continue;
            endif;
            $field[$name] = $value;
        endforeach;

        return $field;
    }
}
