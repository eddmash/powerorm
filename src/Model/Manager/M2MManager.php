<?php
/**
 * This file is part of the powerorm package.
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
use Eddmash\PowerOrm\Model\Query\Expression\F;
use Eddmash\PowerOrm\Model\Query\PrefetchInterface;
use Eddmash\PowerOrm\Model\Query\Queryset;
use Eddmash\PowerOrm\Model\Query\QuerysetInterface;

/**
 * {@inheritdoc}
 */
class M2MManager extends BaseM2MManager implements PrefetchInterface, ManagerInterface
{
    public $filters = [];

    /**
     * @var Model
     *
     * @internal
     */
    private $instance;

    /**
     * @var Model
     *
     * @internal
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

    private $prefetchCacheName;

    private $relatedModelPks;

    public function __construct($kwargs = [])
    {
        $this->instance = ArrayHelper::getValue($kwargs, 'instance');

        /** @var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, 'rel');
        $this->reverse = ArrayHelper::getValue($kwargs, 'reverse', false);

        if (false === $this->reverse) {
            $model = $rel->toModel;
            $this->queryName = $rel->fromField->getRelatedQueryName();
            $this->prefetchCacheName = $rel->fromField->getCacheName();
            $this->fromFieldName = call_user_func($rel->fromField->m2mField);
            $this->toFieldName = call_user_func($rel->fromField->m2mReverseField);
        } else {
            $model = $rel->getFromModel();
            $this->queryName = $rel->fromField->getName();
            $this->prefetchCacheName = $rel->fromField->getCacheName();
            $this->fromFieldName = call_user_func($rel->fromField->m2mReverseField);
            $this->toFieldName = call_user_func($rel->fromField->m2mField);
        }

        $this->through = $rel->through;

        $this->fromField = $this->through->getMeta()->getField($this->fromFieldName);
        $this->toField = $this->through->getMeta()->getField($this->toFieldName);
        $this->filters = [];

        // since we are using the through model
        // we need the pks for the models related to this through model
        $this->relatedModelPks = [];
        foreach ([$this->fromField->getRelatedFields()] as $fields) {
            $throughModelField = $fields[0];
            $relatedModelField = $fields[1];

            $key = sprintf('%s__%s', $this->queryName, $relatedModelField->getName());
            $this->filters[$key] = $this->instance->{$relatedModelField->getAttrName()};

            $this->relatedModelPks[$throughModelField->getName()] = $relatedModelField->getName();
        }
        $this->relatedValues = $this->fromField->getForeignRelatedFieldsValues($this->instance);

        // check to ensure we don't get a scenario like this
        // (new Role())->permissions
        // where role and permission have m2m relationship
        if (empty($this->relatedValues)) {
            throw new ValueError(
                sprintf(
                    '"%s" needs to have a value for field "%s" before this many-to-many relationship can be used.',
                    $this->instance->getMeta()->getNSModelName(),
                    $this->relatedModelPks[$this->fromField->getName()]
                )
            );
        }

        parent::__construct($model);
    }

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
        if ($this->instance->_prefetchedObjectCaches) {
            if ($this->instance->_prefetchedObjectCaches[$this->prefetchCacheName]) {
                return $this->instance->_prefetchedObjectCaches[$this->prefetchCacheName];
            }
        }
        /** @var $qs Queryset */
        $qs = parent::getQueryset();
        return $qs->filter($this->filters);
    }

    public function add()
    {
        if (!$this->through->getMeta()->autoCreated) {
            throw new AttributeError(
                sprintf(
                    "Cannot set values on a ManyToManyField which specifies an intermediary model. 
                Use %s's Manager instead.",
                    $this->through->getMeta()->getModelName()
                )
            );
        }

        $this->addItems($this->fromFieldName, $this->toFieldName, func_get_args());
    }

    public function remove()
    {
        if (!$this->through->getMeta()->autoCreated) {
            $meta = $this->through->getMeta();

            throw new AttributeError(
                sprintf(
                    'Cannot use remove() on a ManyToManyField which specifies '.
                    "an intermediary model. Use %s's Manager instead.",
                    $meta->getNSModelName()
                )
            );
        }
        //todo clear prefetched
        $this->removeItems($this->fromFieldName, $this->toFieldName, func_get_args());
    }

    /**
     * @param $values
     * @param array $kwargs
     *
     * @throws AttributeError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function set($values, $kwargs = [])
    {
        if (!$this->through->getMeta()->autoCreated) {
            throw new AttributeError(
                sprintf(
                    "Cannot set values on a ManyToManyField which specifies an intermediary model. 
                Use %s's Manager instead.",
                    $this->through->getMeta()->getModelName()
                )
            );
        }

        $clear = ArrayHelper::getValue($kwargs, 'clear', false);

        if ($clear) {
            $this->clear();
            $this->addItems($this->fromFieldName, $this->toFieldName, $values);
        } else {
            $oldIds = $this->asArray([$this->toField->getRelatedField()->getAttrName()], true, true)
                ->getResults();

            $newObjects = [];
            foreach ($values as $value) {
                if ($value instanceof Model) {
                    $fkVal = $this->toField->getForeignRelatedFieldsValues($value)[0];
                } else {
                    $fkVal = $value;
                }

                if (in_array($fkVal, $oldIds)) {
                    unset($oldIds[array_search($fkVal, $oldIds)]);
                } else {
                    $newObjects[] = $value;
                }
            }

            $this->remove(...$oldIds);
            $this->add(...$newObjects);
        }
    }

    private function addItems($fromFieldName, $toFieldName, $values = [])
    {
        /* @var $field RelatedField */
        if ($values) {
            $newIds = [];

            foreach ($values as $value) {
                $field = $this->through->getMeta()->getField($this->toFieldName);
                $newIds[] = $field->getForeignRelatedFieldsValues($value)[0];
            }
            $newIds = array_unique($newIds);

            /** @var $throughClass Model */
            $throughClass = $this->through->getMeta()->getNSModelName();

            $oldIds = $throughClass::objects($this->through)->asArray([$toFieldName],
                true, true)->filter(
                [
                    $fromFieldName => $this->relatedValues[0],
                ]
            )->getResults();

            $newIds = array_diff($newIds, $oldIds);

            /** @var $qb QueryBuilder */
            $qb = BaseOrm::getDbConnection()->createQueryBuilder();

            foreach ($newIds as $newId) {
                $qb->insert($this->through->getMeta()->getDbTable());

                $qb->setValue(sprintf('%s_id', $fromFieldName), $qb->createNamedParameter($this->relatedValues[0]));
                $qb->setValue(sprintf('%s_id', $toFieldName), $qb->createNamedParameter($newId));

                // save to db
                $qb->execute();
            }
        }
    }

    public function removeItems($fromFieldName, $toFieldName, $values = [])
    {
        if (empty($values)) {
            return;
        }
        $oldIds = [];

        foreach ($values as $value) {
            if ($value instanceof Model) {
                $field = $this->through->getMeta()->getField($this->toFieldName);
                $oldIds[] = $field->getForeignRelatedFieldsValues($value)[0];
            } else {
                $oldIds[] = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array
    {
        if (!$queryset) {
            $queryset = parent::getQueryset();
        }

        $filter = [sprintf('%s__in', $this->queryName) => $instances];

        $fk = $this->fromField->getLocalRelatedFields()[0];

        $annotations = [];
        $name = sprintf('_prefetch_%s', $fk->getName());
        $model = strtolower($fk->getRelatedModel()->getMeta()->getModelName());
        $field = $fk->toField->getName();
        $annotations[$name] = new F(sprintf('%s__%s', $model, $field));

        $queryset = $queryset->annotate($annotations)->filter($filter);

        return [
            $queryset,
            function (Model $relatedObject) {
                $fk = $this->fromField->getLocalRelatedFields()[0];

                return $relatedObject->{sprintf('_prefetch_%s', $fk->getName())};
            },
            function (Model $instance) {
                $fks = $this->fromField->getForeignRelatedFields();
                // todo should be in literal type, confirm
                return $instance->{$fks[0]->getAttrName()};
            },
            true,
            $this->prefetchCacheName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return parent::getIterator();
    }
}
