<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;

class Func extends BaseExpression
{
    protected $function;
    protected $template = '%s(%s)';
    protected $argJoiner = ', ';
    protected $extra;

    /**
     * @var
     */
    private $expression;

    /**
     * Func constructor.
     *
     * @param Field $outputField
     */
    public function __construct($expression, $kwargs = [])
    {
        $outputField = ArrayHelper::pop($kwargs, 'outputField');

        parent::__construct($outputField);
        $this->expression = $expression;
        $this->extra = $kwargs;
    }

    public function asSql(Connection $connection)
    {
        $expressions = (is_array($this->expression)) ? $this->expression : [$this->expression];
        $sqlParts = [];
        if ($this->extra) :
            $sqlParts[] = implode('', $this->extra);
        endif;
        $params = [];
        /** @var $expression BaseExpression */
        foreach ($expressions as $expression) :
            list($sql, $param) = $expression->asSql($connection);
            $sqlParts[] = $sql;
            $params = array_merge($params, $param);
        endforeach;

        $template = $this->getTemplate($this->function, implode($this->argJoiner, $sqlParts));

        return [$template, $params];
    }

    protected function getTemplate($function, $expression)
    {
        return sprintf($this->template, $function, $expression);
    }
}
