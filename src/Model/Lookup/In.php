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

class In extends BaseLookup
{
    public static $lookupName = 'in';
    public $operator = 'IN';
    protected $rhsValueIsIterable = true;

    public function getLookupOperation($rhs)
    {
        return sprintf('%s %s', $this->operator, $rhs);
    }

    /**@inheritdoc */
    public function processRHS(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        if ($this->valueIsDirect()):
            $element = count($this->rhs);
            $placeholders = implode(', ', array_fill(null, $element, '?'));

            return [sprintf('(%s)', $placeholders), $this->prepareLookupForDb($this->rhs, $connection)];
        else:
            return parent::processRHS($compiler, $connection);
        endif;
    }
}
