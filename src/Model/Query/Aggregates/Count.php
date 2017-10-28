<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Aggregates;

use Eddmash\PowerOrm\Model\Field\IntegerField;
use Eddmash\PowerOrm\Model\Query\Expression\Star;

class Count extends BaseAggregate
{
    protected $function = 'COUNT';
    protected $name = 'COUNT';

    /**
     * {@inheritdoc}
     */
    public function __construct($expression, $distinct = false, $kwargs = [])
    {
        if ('*' === $expression):
            $expression = new Star();
        endif;
        $extra = [];
        $extra['outputField'] = IntegerField::createObject();
        if ($distinct) :
            $extra['distinct'] = 'DISTINCT';
        endif;

        parent::__construct(
            $expression,
            $extra + $kwargs
        );
    }
}
