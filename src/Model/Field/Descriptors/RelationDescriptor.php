<?php
/**
 * This file is part of the store.
 *
 *
 * Created by : Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
 * On Date : 1/7/19 9:32 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Descriptors;

use Eddmash\PowerOrm\Model\Manager\ManagerInterface;
use Eddmash\PowerOrm\Model\Model;

interface RelationDescriptor
{
    /**
     * @param Model $modelInstance
     *
     * @return ManagerInterface
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    public function getManager(Model $modelInstance);

    /**
     * The name of the manager class to use.
     *
     * @return string
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    public function getManagerClass(): string;
}
