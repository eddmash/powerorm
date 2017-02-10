<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;

class StartsWith extends PatternLookup
{
    public static $lookupName = 'startswith';

    public function processRHS(Connection $connection)
    {
        $this->rhs = sprintf('%s%%', $this->rhs);

        return parent::processRHS($connection);
    }
}
