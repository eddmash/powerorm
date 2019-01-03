<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/2/19
 * Time: 7:01 PM.
 */

namespace Eddmash\PowerOrm\Model\Query;

use Eddmash\PowerOrm\Model\Model;

/**
 * Implementation of this interface means it can be used to perform prefetch queries.
 */
interface PrefetchInterface
{
    /**
     * Returns a queryset to be used to perform querysets.
     *
     * @param array                  $instances
     * @param QuerysetInterface|null $queryset
     *
     * @return Queryset
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array;

    public function isCached(Model $model): bool;
}
