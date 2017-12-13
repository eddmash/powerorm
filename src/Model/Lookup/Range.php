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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class Range extends BaseLookup
{
    public static $lookupName = 'range';
    protected $operator = 'BETWEEN %s AND %s';
    protected $rhsValueIsIterable = true;

    public function getLookupOperation($rhs)
    {
        return sprintf($this->operator, $rhs[0], $rhs[1]);
    }

    /**@inheritdoc*/
    public function processRHS(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        if ($this->valueIsDirect()):
            $element = count($this->rhs);
            $placeholders = array_fill(null, $element, '?');

            return [$placeholders, $this->prepareLookupForDb($this->rhs, $connection)];
        endif;

        return parent::processRHS($compiler, $connection);
    }
}
