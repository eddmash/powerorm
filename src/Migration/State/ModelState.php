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
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

/**
 * Represents a PowerOrm Model.
 *
 * We don't use the actual Model class as it's not designed to have its options changed -instead, we mutate this one
 * and then render it into a Model as required.
 *
 * Note that while you are allowed to mutate .fields, you are not allowed to mutate the Field instances inside there
 * themselves - you must instead assign new ones, as these are not detached during a clone.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ModelState extends Object
{
    public $name;
    public $fields = [];
    public $meta;
    public $extends;

    private $fakeNamespace = 'Eddmash\PowerOrm\__Fake__\Model';

    public function __construct($name, $fields, $kwargs = [])
    {
        $this->name = $name;
        $this->fields = $fields;
        BaseOrm::configure($this, $kwargs);
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function fromModel($model, $excludeRels = false)
    {
        $fields = [];

        /** @var $field Field */
        foreach ($model->meta->localFields as $name => $field) :
            try {
                $fields[$name] = $field->deepClone();
            } catch (\Exception $e) {
                throw new TypeError(sprintf("Couldn't reconstruct field %s on %s: %s", $name, $model->meta->modelName));
            }
        endforeach;

        if ($excludeRels !== false):
            foreach ($model->meta->localManyToMany as $name => $field) :
                try {
                    $fields[$name] = $field->deepClone();
                } catch (\Exception $e) {
                    throw new TypeError(sprintf("Couldn't reconstruct field %s on %s: %s", $name,
                        $model->meta->modelName));
                }
            endforeach;
        endif;

        $overrides = $model->meta->getOverrides();
        $meta = [];
        $ignore = ['registry'];
        foreach ($overrides as $name => $value) :
            if(in_array($name, $ignore)):
                continue;
            endif;
            $meta[$name] = $value;
        endforeach;

        $kwargs = [
            'meta' => $meta,
            'extends' => $model->getParent()->getName(),
        ];

        return new static($model->meta->modelName, $fields, $kwargs);
    }

    /**
     * Converts the current modelState into a model.
     *
     * @param Registry $registry
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function toModel($registry) {

        $metaData = $this->meta;
        $extends = $this->extends;

        $model = $this->_defineLoadClass($this->name, $extends);
        $model->init($this->fields, ['meta' => $metaData, 'registry' => $registry]);

        return $model;
    }

    public static function createObject($name, $field, $kwargs) {
        return new static($name, $field, $kwargs);
    }
    /**
     * @param string $className
     * @param string $extends
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _defineLoadClass($className, $extends = '') {
        $className = ucfirst($className);
        // we create a new namespace and define new classes because,
        // we might be dealing with a model that has been dropped
        // Meaning if we try to load the model using the normal way,
        // we will get and error of model does not exist
        $class = 'namespace %1$s;

            class %2$s extends \%3$s{

                 public function unboundFields(){return [];}
            }';

        if(empty($extends)):
            $extends = 'Eddmash\PowerOrm\Model\Model';
        endif;

        $class = sprintf($class, $this->fakeNamespace, $className, $extends);

        $className = sprintf('%s\%s', $this->fakeNamespace, $className);

        if(!class_exists($className, false)):
            eval($class);
        endif;

        return new $className();
    }

    public function deepClone() {
        return static::createObject($this->name, $this->fields, $this->kwargs);
    }

    public function __toString() {
        return sprintf("<ModelState: '%s'>", $this->name);
    }
}
