<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class CombinedExpression extends BaseExpression
{
    private $lhs;

    private $connector;

    private $rhs;

    /**
     * {@inheritdoc}
     */
    public function __construct($lhs, $connector, $rhs, Field $outputField = null)
    {
        parent::__construct($outputField);
        $this->lhs = $lhs;
        $this->connector = $connector;
        $this->rhs = $rhs;
    }

    public function getSourceExpressions()
    {
        return [$this->lhs, $this->rhs];
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceExpressions($expression)
    {
        $this->lhs = $expression[0];
        $this->rhs = $expression[1];
    }

    /**
     * {@inheritdoc}
     */
    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        try {
            $lhsOutputField = $this->lhs->getOutputField();
        } catch (FieldError $error) {
            $lhsOutputField = null;
        }

        try {
            $rhsOutputField = $this->rhs->getOutputField();
        } catch (FieldError $error) {
            $rhsOutputField = null;
        }

        //todo handle time fields

        $expression = [];
        $expressionParams = [];

        list($sql, $params) = $compiler->compile($this->lhs);
        $expression[] = $sql;
        $expressionParams = array_merge($expressionParams, $params);
        list($sql, $params) = $compiler->compile($this->rhs);
        $expression[] = $sql;
        $expressionParams = array_merge($expressionParams, $params);

        $sql = implode($this->connector, $expression);

        return [$sql, $expressionParams];
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
    ) {
        $obj = parent::resolveExpression(
            $resolver,
            $allowJoins,
            $reuse,
            $summarize,
            $forSave
        );

        $obj->summarize = $summarize;
        $obj->lhs = $obj->lhs->resolveExpression($resolver, $allowJoins, $reuse, $summarize, $forSave);
        $obj->rhs = $obj->rhs->resolveExpression($resolver, $allowJoins, $reuse, $summarize, $forSave);

        return $obj;
    }
}
