<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/2/19
 * Time: 8:44 PM.
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
