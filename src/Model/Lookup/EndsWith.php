<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;

class EndsWith extends PatternLookup
{
    public static $lookupName = 'endswith';

    public function processRHS(Connection $connection)
    {
        $this->rhs = sprintf('%%%s', $this->rhs);

        return parent::processRHS($connection);
    }
}
