<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * @method self all()
 * @method self get()
 * @method self filter()
 * @method self exists()
 * @method self exclude()
 * @method self prefetchRelated()
 * @method self selectRelated()
 * @method self annotate()
 * @method self aggregate()
 *
 * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
 */
interface ManagerInterface extends \IteratorAggregate
{
    /**
     * @return Queryset
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getQueryset();
}
