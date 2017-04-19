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

class Range extends BaseLookup
{
    public static $lookupName = 'range';
    protected $operator = "BETWEEN %s AND %s";

    public function getLookupOperation($rhs)
    {
        return sprintf('%s %s', $this->operator, $rhs);
    }

    public function prepareLookup()
    {
        $preparedValues = [];
        foreach ($this->rhs as $rh) :
            if ($this->prepareRhs && method_exists($this->lhs->getOutputField(), 'prepareValue')):

                $preparedValues[] = $this->lhs->getOutputField()->prepareValue($rh);
            endif;
        endforeach;

        return $preparedValues;
    }
}
