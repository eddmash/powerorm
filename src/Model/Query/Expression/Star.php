<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Doctrine\DBAL\Connection;

class Star extends BaseExpression
{
    public function asSql(Connection $connection)
    {
        return ['*', []];
    }

}
