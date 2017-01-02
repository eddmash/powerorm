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

class In extends BaseLookup
{
    public static $lookupName = 'in';
    public $operator = 'IN';

    public function getLookupOperation($rhs)
    {
        return sprintf('%s (%s)', $this->operator, $rhs);
    }

    public function processRHS(Connection $connection, QueryBuilder $queryBuilder)
    {
        return $this->rhs;
    }
}
