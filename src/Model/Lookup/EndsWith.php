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
use Doctrine\DBAL\Query\QueryBuilder;

class EndsWith extends PatternLookup
{
    public static $lookupName = 'endswith';

    public function processRHS(Connection $connection, QueryBuilder $queryBuilder)
    {
        $this->rhs = sprintf('%%%s', $this->rhs);

        return parent::processRHS($connection, $queryBuilder);
    }
}
