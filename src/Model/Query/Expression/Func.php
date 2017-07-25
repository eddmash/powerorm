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
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;

/**
 * Base type of all expressions that involve database functions like COALESCE and LOWER, or aggregates like SUM
 * Class Func.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
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
     * @param array $kwargs
     * @internal param Field $outputField
     */
    public function __construct($kwargs = [])
    {
        $expression = ArrayHelper::pop($kwargs, 'expression');
        $outputField = ArrayHelper::pop($kwargs, 'outputField');

        parent::__construct($outputField);
        $this->expression = $this->parseExpression($expression);
        $this->extra = $kwargs;
    }

    /**
     * @return BaseExpression[]
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getSourceExpressions()
    {
        return $this->expression;
    }

    public function setSourceExpressions($expression)
    {
        return $this->expression = $expression;
    }


    public function asSql(Connection $connection)
    {
//        $expressions = (is_array($this->expression)) ? $this->expression : [$this->expression];
        $sqlParts = [];
        if ($this->extra) :
            $sqlParts[] = implode('', $this->extra);
        endif;
        $params = [];

        foreach ($this->getSourceExpressions() as $expression) :
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
