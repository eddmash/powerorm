<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Doctrine\DBAL\Query\QueryBuilder;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Class M2MQueryset.
 *
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class M2MManager extends BaseM2MManager
{
    public $filters = [];
    /**
     * @var Model
     */
    private $instance;
    /**
     * @var Model
     */
    private $through;

    /**
     * @var \Eddmash\PowerOrm\Model\Field\RelatedField
     */
    private $fromField;

    /**
     * @var bool
     */
    private $reverse;

    /**
     * @param array $kwargs
     *
     * @return static
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function createObject($kwargs = [])
    {
        return new static($kwargs);
    }

    public function getQueryset()
    {
        /** @var $qs Queryset */
        $qs = parent::getQueryset();

        return $qs->filter($this->filters);
    }

    public function __construct($kwargs = [])
    {
        $this->instance = ArrayHelper::getValue($kwargs, 'instance');

        /** @var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, 'rel');
        $this->reverse = ArrayHelper::getValue($kwargs, 'reverse', false);

        if (false === $this->reverse):
            $model = $rel->toModel;
            $this->queryName = $rel->fromField->getRelatedQueryName();
            $this->fromFieldName = call_user_func($rel->fromField->m2mField);
            $this->toFieldName = call_user_func($rel->fromField->m2mReverseField);
        else:
            $model = $rel->getFromModel();
            $this->queryName = $rel->fromField->getName();
            $this->fromFieldName = call_user_func($rel->fromField->m2mReverseField);
            $this->toFieldName = call_user_func($rel->fromField->m2mField);
        endif;

        $this->through = $rel->through;

        $this->fromField = $this->through->getMeta()->getField($this->fromFieldName);
        $this->toField = $this->through->getMeta()->getField($this->toFieldName);
        $this->filters = [];

        foreach ([$this->fromField->getRelatedFields()] as $fields) :
            $rhsField = $fields[1];

            $key = sprintf('%s__%s', $this->queryName, $rhsField->getName());
            $this->filters[$key] = $this->instance->{$rhsField->getAttrName()};
        endforeach;

        $this->relatedValues = $this->fromField->getForeignRelatedFieldsValues($this->instance);
        if (empty($this->relatedValues)):
            throw new ValueError(
                sprintf(
                    '"%s" needs to have a value for field "%s" before this many-to-many relationship can be used.',
                    $this->instance->getMeta()->getNamespacedModelName(),
                    $this->fromFieldName
                )
            );
        endif;

        parent::__construct($model);
    }

    public function add()
    {
        if (!$this->through->getMeta()->autoCreated) :
            throw new AttributeError(
                sprintf(
                    "Cannot set values on a ManyToManyField which specifies an intermediary model. 
                Use %s's Manager instead.",
                    $this->through->getMeta()->getModelName()
                )
            );
        endif;

        $this->addItems($this->fromFieldName, $this->toFieldName, func_get_args());
    }

    public function remove()
    {
        if (!$this->through->getMeta()->autoCreated):
            $meta = $this->through->getMeta();

            throw new AttributeError(
                sprintf(
                    'Cannot use remove() on a ManyToManyField which specifies '.
                    "an intermediary model. Use %s's Manager instead.",
                    $meta->getNamespacedModelName()
                )
            );
        endif;
        //todo clear prefetched
        $this->removeItems($this->fromFieldName, $this->toFieldName, func_get_args());
    }

    public function set($values, $kwargs = [])
    {
        if (!$this->through->getMeta()->autoCreated) :
            throw new AttributeError(
                sprintf(
                    "Cannot set values on a ManyToManyField which specifies an intermediary model. 
                Use %s's Manager instead.",
                    $this->through->getMeta()->getModelName()
                )
            );
        endif;

        $clear = ArrayHelper::getValue($kwargs, 'clear', false);

        if ($clear) :
            $this->clear();
            $this->addItems($this->fromFieldName, $this->toFieldName, $values);
        else:

            $oldVals = $this->asArray([$this->toField->getRelatedField()->getAttrName()], true, true);
            $oldIds = $this->evalQueryset($oldVals);

            $newObjects = [];
            foreach ($values as $value) :
                if ($value instanceof Model):
                    $fkVal = $this->toField->getForeignRelatedFieldsValues($value)[0];
                else:
                    $fkVal = $value;
                endif;

                if (in_array($fkVal, $oldIds)):
                    unset($oldIds[array_search($fkVal, $oldIds)]);
                else:
                    $newObjects[] = $value;
                endif;
            endforeach;

            $this->remove(...$oldIds);
            $this->add(...$newObjects);
        endif;
    }

    private function addItems($fromFieldName, $toFieldName, $values = [])
    {
        /* @var $field RelatedField */
        if ($values) :
            $newIds = [];

            foreach ($values as $value) :
                $field = $this->through->getMeta()->getField($this->toFieldName);
                $newIds[] = $field->getForeignRelatedFieldsValues($value)[0];
            endforeach;
            $newIds = array_unique($newIds);

            /** @var $throughClass Model */
            $throughClass = $this->through->getMeta()->getNamespacedModelName();

            $oldVals = $throughClass::objects($this->through)->asArray([$toFieldName], true, true)->filter(
                [
                    $fromFieldName => $this->relatedValues[0],
                ]
            );
            $oldIds = $this->evalQueryset($oldVals);

            $newIds = array_diff($newIds, $oldIds);

            /** @var $qb QueryBuilder */
            $qb = BaseOrm::getDbConnection()->createQueryBuilder();

            foreach ($newIds as $newId) :
                $qb->insert($this->through->getMeta()->getDbTable());

                $qb->setValue(sprintf('%s_id', $fromFieldName), $qb->createNamedParameter($this->relatedValues[0]));
                $qb->setValue(sprintf('%s_id', $toFieldName), $qb->createNamedParameter($newId));

                // save to db
                $qb->execute();
            endforeach;

        endif;
    }

    public function removeItems($fromFieldName, $toFieldName, $values = [])
    {
        if (empty($values)):
            return;
        endif;
        $oldIds = [];

        foreach ($values as $value) :
            if ($value instanceof Model):

                $field = $this->through->getMeta()->getField($this->toFieldName);
                $oldIds[] = $field->getForeignRelatedFieldsValues($value)[0];
            else:
                $oldIds[] = $value;
            endif;
        endforeach;
    }

    private function evalQueryset(Queryset $queryset)
    {
        $oldIds = [];
        foreach ($queryset as $oldVal) :
            $oldIds[] = $oldVal;
        endforeach;

        return $oldIds;
    }
}
