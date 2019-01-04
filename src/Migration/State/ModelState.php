<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\State;

use Eddmash\PowerOrm\App\Registry;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Migration\Model\MigrationModel;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use ReflectionObject;

/**
 * Represents a PowerOrm Models.
 *
 * We don't use the actual Models class as it's not designed to have its options changed -instead, we mutate this one
 * and then render it into a Models as required.
 *
 * Note that while you are allowed to mutate .fields, you are not allowed to mutate the Field instances inside there
 * themselves - you must instead assign new ones, as these are not detached during a clone.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ModelState extends BaseObject
{
    public $name;

    protected $meta = [];

    /** @var Field[] */
    public $fields = [];

    public $extends;

    private $fromDisk = false;

    public function __construct($name, $fields, $kwargs = [])
    {
        $this->name = $name;
        $this->fields = $fields;
        ClassHelper::setAttributes($this, $kwargs);
    }

    /**
     * Takes a model returns a ModelState representing it.
     *
     * @param Model      $model
     * @param bool|false $excludeRels
     *
     * @return static
     *
     * @throws TypeError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function fromModel(Model $model, $excludeRels = false)
    {
        $fields = [];

        /** @var $field Field */
        foreach ($model->getMeta()->localFields as $name => $field) {
            try {
                $fields[$name] = $field->deepClone();
            } catch (\Exception $e) {
                throw new TypeError(
                    sprintf(
                        "Couldn't reconstruct field %s on %s: %s",
                        $name,
                        $model->getMeta()->getNSModelName()
                    )
                );
            }
        }

        if (false == $excludeRels) {
            foreach ($model->getMeta()->localManyToMany as $name => $field) {
                try {
                    $fields[$name] = $field->deepClone();
                } catch (\Exception $e) {
                    throw new TypeError(
                        sprintf(
                            "Couldn't reconstruct field %s on %s: %s",
                            $name,
                            $model->getMeta()->getNSModelName()
                        )
                    );
                }
            }
        }

        $overrides = $model->getMeta()->getOverrides();
        $meta = [];
        $ignore = ['registry'];
        foreach ($overrides as $name => $value) {
            if (in_array($name, $ignore)) {
                continue;
            }
            $meta[$name] = $value;
        }

        $extends = '';

        /** @var $immediateParent ReflectionObject */
        list($concreteParent, $immediateParent) = Model::getHierarchyMeta($model);

        if ($immediateParent) {
            if ($immediateParent->isAbstract()) {
                $extends = (is_null($concreteParent)) ? '' : $concreteParent;
            } else {
                $extends = $immediateParent->getName();
            }
        }

        $kwargs = [
            'meta' => $meta,
            'extends' => $extends,
        ];

        return new static($model->getMeta()->getNSModelName(), $fields, $kwargs);
    }

    /**
     * Converts the current modelState into a model.
     *
     * @param Registry $registry
     *
     * @return Model
     *
     * @throws TypeError
     * @throws \Eddmash\PowerOrm\Exception\FieldError
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     * @throws \Eddmash\PowerOrm\Exception\MethodNotExtendableException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function toModel(&$registry)
    {
        $metaData = $this->getMeta();
        $extends = $this->extends;

        $className = $this->name;

        // if we are loading migrations from disk we need to namespace different
        // so that we can be able to compare the two states. this way we avoid
        // possibility of loading the same state information about an app
        // i.e. we might end up always loading the state of the apps based on
        // whats currently shown the models
        if ($this->fromDisk) {
            $className = sprintf(
                '%s\\%s',
                Model::FAKENAMESPACE,
                $className
            );
            if ($extends) {
                $extends = sprintf(
                    '%s\\%s',
                    Model::FAKENAMESPACE,
                    $extends
                );
            }
        }

        $model = $this->createInstance(
            $className,
            $extends
        );
        $fields = [];
        foreach ($this->fields as $name => $field) {
            $fields[$name] = $field->deepClone();
        }

        $model->setupClassInfo($fields, ['meta' => $metaData, 'registry' => $registry]);

        return $model;
    }

    public static function createObject($name, $field, $kwargs)
    {
        return new static($name, $field, $kwargs);
    }

    public function getFieldByName($name)
    {
        if (ArrayHelper::hasKey($this->fields, $name)) {
            return ArrayHelper::getValue($this->fields, $name);
        }

        throw new ValueError(sprintf('No field called [ %s ] on model [ %s ]', $name, $this->name));
    }

    /**
     * Defines a new model class.
     *
     * we create a new namespace and define new classes because, we might be
     * dealing with a model that has been dropped
     * Meaning if we try to load the model using the normal way, we will get
     * and error of model does not exist.
     *
     * @param string $className
     * @param string $extends
     *
     * @return Model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private static function createInstance($className, $extends = '')
    {
        $className = MigrationModel::defineClass($className, $extends);
        return new $className();
    }

    public function deepClone()
    {
        $fields = [];
        /** @var $field Field */
        foreach ($this->fields as $name => $field) {
            $fields[$name] = $field->deepClone();
        }

        $model = static::createObject(
            $this->name,
            $fields,
            ['meta' => $this->getMeta(), 'extends' => $this->extends]
        );
        $model->fromDisk($this->fromDisk);

        return $model;
    }

    public function __toString()
    {
        return (string) sprintf("<ModelState: '%s'>", $this->name);
    }

    public function &getMeta()
    {
        return $this->meta;
    }

    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    public function fromDisk($fromDisk)
    {
        $this->fromDisk = $fromDisk;
    }
}
