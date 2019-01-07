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

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

/**
 * Base type of all expressions that involve database functions like COALESCE and LOWER, or aggregates like SUM
 * Class Func.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Func extends BaseExpression
{
    protected $function;

    protected $template = '%s(%s)';

    protected $argJoiner = ', ';

    protected $extra = [];

    /**
     * @var BaseExpression[]
     */
    protected $expression;

    /**
     * Func constructor.
     *
     * @param array $kwargs
     *
     * @internal param Field $outputField
     */
    public function __construct($kwargs = [])
    {
        $expression = ArrayHelper::pop($kwargs, 'expression');
        $outputField = ArrayHelper::pop($kwargs, 'outputField', null);

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

    /**
     * {@inheritdoc}
     */
    public function resolveExpression(
        ExpResolverInterface $resolver,
        $allowJoins = true,
        $reuse = null,
        $summarize = false,
        $forSave = false
    )
    {
        $obj = clone $this;
        $obj->summarize = $summarize;

        foreach ($obj->expression as $key => $item) {
            $obj->expression[$key] = $item->resolveExpression(
                $resolver,
                $allowJoins,
                $reuse,
                $summarize,
                $forSave
            );
        }

        return $obj;
    }

    /**@inheritdoc */
    public function asSql(
        CompilerInterface $compiler,
        ConnectionInterface $connection,
        $function = null
    )
    {
        $sqlParts = [];

        if (!is_null($function)) {
            $func = $function;
        } else {
            $func = ArrayHelper::pop(
                $this->extra,
                'function',
                $this->function
            );
        }

        if ($this->extra) {
            $sqlParts[] = implode('', $this->extra);
        }
        $params = [];

        foreach ($this->expression as $expression) {
            list($sql, $param) = $compiler->compile($expression);
            $sqlParts[] = $sql;
            $params = array_merge($params, $param);
        }

        $template = $this->getTemplate($func, implode($this->argJoiner, $sqlParts));

        return [$template, $params];
    }

    protected function getTemplate($function, $expression)
    {
        return sprintf($this->template, $function, $expression);
    }

    public function __debugInfo()
    {
        return [
            'extra' => $this->extra,
            'function' => $this->function,
            'argJoiner' => $this->argJoiner,
            'template' => $this->template,
        ];
    }
}
