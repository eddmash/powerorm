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

interface ManagerInterface extends \IteratorAggregate
{
    /**
     * @return Queryset
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getQueryset();
}
