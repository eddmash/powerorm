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

use Eddmash\PowerOrm\BaseObject;

class BaseManager extends BaseObject
{
    public function __call($name, $arguments)
    {
        // Note: value of $name is case sensitive.
        echo "Calling object method '$name' "
            .implode(', ', $arguments)."\n";
    }
}
