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
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class OrderBy extends BaseExpression
{
    protected $template = '%s %s ';

    /**
     * @var Field
     */
    private $expression;

    /**
     * @var bool
     */
    private $descending;

    /**
     * @var
     */
    private $nullsFirst;

    /**
     * @var bool
     */
    private $nullsLast;

    /**
     * OrderBy constructor.
     *
     * @param BaseExpression $expression
     * @param bool           $descending
     * @param array          $kwargs
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function __construct(
        BaseExpression $expression,
        $descending = false,
        $kwargs = []
    ) {
        parent::__construct(null);
        $nullsFirst = ArrayHelper::getValue($kwargs, 'nullFirst', false);
        $nullsLast = ArrayHelper::getValue($kwargs, 'nullsLast', false);
        if ($nullsFirst && $nullsLast) {
            throw new ValueError('nulls_first and nulls_last are mutually exclusive');
        }
        $this->descending = $descending;
        $this->nullsFirst = $nullsFirst;
        $this->nullsLast = $nullsLast;

        if (!$expression instanceof BaseExpression) {
            throw new ValueError('expression must be an expression type');
        }
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceExpressions()
    {
        return [$this->expression];
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceExpressions($expression)
    {
        return $expression[0];
    }

    /**@inheritdoc */
    public function asSql(
        CompilerInterface $compiler,
        ConnectionInterface $connection,
        $template = null
    ) {
        if (is_null($template)) {
            if ($this->nullsLast) {
                $template = sprintf('%s NULLS LAST', $this->template);
            } elseif ($this->nullsFirst) {
                $template = sprintf('%s NULLS FIRST', $this->template);
            }
        }

        list($expSql, $expParams) = $compiler->compile($this->expression);

        $params = [
            $expSql,
            ($this->descending) ? 'DESC' : 'ASC',
        ];

        $template = ($template) ? $template : $this->template;

        return [vsprintf($template, $params), $expParams];
    }

    /**
     * {@inheritdoc}
     */
    public function descendingOrder($kwargs = [])
    {
        $this->descending = true;
    }

    /**
     * {@inheritdoc}
     */
    public function ascendingOrder($kwargs = [])
    {
        $this->descending = false;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseOrdering()
    {
        $this->descending = !$this->descending;
    }

    public function getGroupByCols()
    {
        $cols = [];
        foreach ($this->getSourceExpressions() as $sourceExpression) {
            $cols[] = $sourceExpression->getGroupByCols();
        }

        return $cols;
    }
}
