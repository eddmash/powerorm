<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/20/17
 * Time: 7:38 PM.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\PrefetchInterface;
use Eddmash\PowerOrm\Model\Query\Queryset;
use Eddmash\PowerOrm\Model\Query\QuerysetInterface;

/**
 * Gets related data from the one side of the relationship.
 *
 * user has many cars so this will query cars related to a particular user in
 * this the default attribute to be used will be ::
 *
 *  $user->car_set->all()
 *
 * @return Queryset
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class O2MManager extends BaseM2OManager implements PrefetchInterface
{
    public $reverse;

    public $instance;

    public $filters;

    /**
     * @var ForeignObjectRel
     */
    protected $relation;

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

    /**
     * {@inheritdoc}
     */
    public function __construct($kwargs = [])
    {
        $this->instance = ArrayHelper::getValue($kwargs, 'instance');
        $this->reverse = ArrayHelper::getValue($kwargs, 'reverse', false);
        /* @var ForeignObjectRel $rel */
        $this->relation = $rel = ArrayHelper::getValue($kwargs, 'rel');

        $model = $rel->getFromModel();
        //todo
        $fromField = $rel->getRelatedField();
        $toField = $rel->fromField;
        $value = $this->instance->{$fromField->getAttrName()};
        $filter[$toField->getName()] = $value;

        $this->filters = $filter;

        parent::__construct($model);
    }

    public function getQueryset()
    {
        $cacheName = $this->relation->getCacheName();
        if ($this->instance->_prefetchedObjectCaches) {
            if ($this->instance->_prefetchedObjectCaches[$cacheName]) {
                return $this->instance->_prefetchedObjectCaches[$cacheName];
            }
        }

        /** @var $qs Queryset */
        $qs = parent::getQueryset();

        return $qs->filter($this->filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return parent::getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array
    {
        if (!$queryset) {
            $queryset = parent::getQueryset();
        }

        $fromField = $this->relation->fromField;
        $relValCallable = function (Model $model) use ($fromField) {
            return $fromField->getLocalRelatedFieldsValues($model)[0];
        };
        $instaceValCallable = function (Model $model) use ($fromField) {
            $r = $fromField->getForeignRelatedFieldsValues($model);
            return $r[0];
        };
        $filter = [sprintf('%s__in', $fromField->getName()) => $instances];
        $queryset = $queryset->filter($filter);
        $cachename = $this->relation->getCacheName();

        $instMap = [];
        foreach ($instances as $instance) {
            $instMap[$instaceValCallable($instance)] = $instance;
        }

        foreach ($queryset as $rel) {
            $instance = ArrayHelper::getValue($instMap, $relValCallable($rel));
            $name = $fromField->getName();
            $rel->{$name} = $instance;
        }
        return [
            $queryset,
            $relValCallable,
            $instaceValCallable,
            true,
            $cachename,
        ];
    }
}
