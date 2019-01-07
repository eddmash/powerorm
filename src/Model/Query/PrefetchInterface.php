<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query;

/**
 * Implementation of this interface means it can be used to perform prefetch queries.
 */
interface PrefetchInterface
{
    /**
     * Returns a queryset to be used to perform querysets.
     *
     * @param array $instances
     * @param QuerysetInterface|null $queryset
     *
     * @return array containing the following:
     *               - relQs QuerysetInterface used to fetch related records
     *               - valOnRelatedCallable callable that returns the value of the related model, given a model
     *               e.g. if we are fetching all roles belonging to a user,
     *               this callable will return the value of user in the role model pass
     *               - valOnInstanceCallable callable that returns value of the instance, e.g. value of a user
     *               - cacheName string indicates where to store the related objects.
     */
    public function getPrefetchQueryset(array $instances, QuerysetInterface $queryset = null): array;
}
