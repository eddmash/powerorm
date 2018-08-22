<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Serializer;

use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

class SimpleObjectSerializer implements SerializerInterface
{
    public $objects;

    public $_fields;

    public $items;

    /**
     * @var array
     */
    protected $selectedFields;

    public static function serialize($items, $fields = [])
    {
        return (new static())->doSerialize($items, $fields);
    }

    private function doSerialize($items, $fields = [])
    {
        $this->items = $items;
        $this->selectedFields = $fields;
        $this->startSerialization();

        if (!is_array($items) && !$items instanceof Queryset):
            $items = [$items];
        endif;
        /** @var $item Model */
        foreach ($items as $item) :
            $this->startObject($item);
            $concreteModel = $item->getMeta()->getConcreteModel();
            $localFields = $concreteModel->getMeta()->localFields;
            foreach ($localFields as $field) :
                if ($field->isSerializable()):
                    if (!$field->isRelation):
                        if (empty($this->selectedFields) ||
                            in_array($field->getAttrName(), $this->selectedFields)):
                            $this->handleField($item, $field);
                        else:
                            // instead of user_id we need user
                            $name = substr(
                                $field->getAttrName(),
                                0,
                                -3
                            );
                            if (empty($this->selectedFields) ||
                                in_array($name, $this->selectedFields)):
                                $this->handleForeignField($item, $field);
                            endif;
                        endif;
                    endif;
                endif;
            endforeach;
            $m2mFields = $concreteModel->getMeta()->localManyToMany;
            foreach ($m2mFields as $m2mField) :
                if ($m2mField->isSerializable()):
                    if (empty($this->selectedFields) ||
                        in_array($m2mField->getAttrName(), $this->selectedFields)):
                        $this->handleM2MField($item, $m2mField);
                    endif;
                endif;
            endforeach;
            $this->endObject($item);
        endforeach;

        $this->endSerialization();

        return $this->getValue();
    }

    public function handleForeignField(Model $model, Field $field)
    {
        // todo naturalkeys
        $this->_fields[$field->getName()] = $field->valueToString($model);
    }

    /**
     * @param Model $model
     * @param Field $field
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function handleM2MField(Model $model, Field $field)
    {
        $vals = [];
        /** @var $field ManyToManyField */
        if ($field->relation->through->getMeta()->autoCreated):
            //todo handle natuaral keys
            $m2mValues = function (Model $model) {
                return $model->getPkValue();
            };

            foreach ($model->{$field->getName()}->all() as $item) :
                $vals[] = $m2mValues($item);
            endforeach;
        endif;
        $this->_fields[$field->getName()] = $vals;
    }

    public function handleField(Model $model, Field $field)
    {
        $this->_fields[$field->getName()] = $field->valueToString($model);
    }

    /**
     * Invoked when serialization starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function startSerialization()
    {
        $this->_fields = null;
        $this->objects = [];
    }

    /**
     * invoked when serialization ends.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function endSerialization()
    {
        // TODO: Implement handleEndSerialization() method.
    }

    /**
     * Invoked when creating of a serial representation of an item starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function startObject(Model $model)
    {
        $this->_fields = [];
    }

    /**
     * Invoked when ending the serial representation of an item starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function endObject(Model $model)
    {
        $this->objects[] = $this->dumpObject($model);
        $this->_fields = null;
    }

    /**
     * Returns the serialize object/objects.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getValue()
    {
        return $this->objects;
    }

    protected function dumpObject(Model $model)
    {
        $object['model'] = $model->getMeta()->getNSModelName();
        $object['fields'] = $this->_fields;

        return $object;
    }
}
