<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;

class IsNull extends BaseLookup
{
    public static $lookupName = 'isnull';
    public $operator = ' is null';

    public function asSql(Connection $connection)
    {
        if($this->rhs):
            return [sprintf("%s IS NULL", $params)];
        endif;
    }
}
