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

use Eddmash\PowerOrm\Exceptions\TypeError;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

class ModelState extends Object
{
    protected $name;
    protected $fields = [];

    public function __construct($name, $fields, $options = [])
    {
        $this->name = $this->normalizeKey($name);
        $this->fields = $fields;
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

        foreach ($model->meta->localFields as $name => $field) :
            $name = Tools::normalizeKey($name);
            try {
                $fields[$name] = $field->deepClone();
            } catch (\Exception $e) {
                throw new TypeError(sprintf("Couldn't reconstruct field %s on %s: %s", $name, $model->meta->modelName));
            }
        endforeach;

        if ($excludeRels !== false):
            foreach ($model->meta->localManyToMany as $name => $field) :
                $name = Tools::normalizeKey($name);
                try {
                    $fields[$name] = $field->deepClone();
                } catch (\Exception $e) {
                    throw new TypeError(sprintf("Couldn't reconstruct field %s on %s: %s", $name,
                        $model->meta->modelName));
                }
            endforeach;
        endif;

        return new static($model->meta->modelName, $fields);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
