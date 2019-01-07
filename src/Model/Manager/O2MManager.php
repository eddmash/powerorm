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
class O2MManager extends M2OManager implements PrefetchInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array
    {
        if (!$queryset) {
            $queryset = $this->getQueryset();
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

    public function isCached(Model $model): bool
    {
        // TODO: Implement isCached() method.
    }
}
