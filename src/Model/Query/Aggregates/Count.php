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




use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\IntegerField;
use Eddmash\PowerOrm\Model\Query\Expression\Func;
use Eddmash\PowerOrm\Model\Query\Expression\Star;

class Count extends Func
{
    protected $function = "COUNT";
    protected $name = "COUNT";
    /**
     * @var bool
     */
    private $distinct;

    /**
     * {@inheritdoc}
     */
    public function __construct($expression, $distinct=false,Field $outputField=null)
    {
        if($expression==="*"):
            $expression = new Star();
        endif;
        $distinctVal = ($distinct)?"DISTINCT":"";
        parent::__construct($expression, [$outputField=IntegerField::createObject(), $distinct=$distinctVal]);
    }


}