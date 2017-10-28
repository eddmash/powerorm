<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Doctrine\DBAL\Connection;
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

    public function __construct(BaseExpression $expression, $descending = false, $kwargs = [])
    {
        parent::__construct(null);
        $nullsFirst = ArrayHelper::getValue($kwargs, 'nullFirst', false);
        $nullsLast = ArrayHelper::getValue($kwargs, 'nullsLast', false);
        if ($nullsFirst && $nullsLast):
            throw new ValueError('nulls_first and nulls_last are mutually exclusive');
        endif;
        $this->descending = $descending;
        $this->nullsFirst = $nullsFirst;
        $this->nullsLast = $nullsLast;

        if (!$expression instanceof BaseExpression):
            throw new ValueError('expression must be an expression type');
        endif;
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

    public function asSql(CompilerInterface $compiler, Connection $connection, $template = null)
    {
        if (!is_null($template)):
            if ($this->nullsLast):
                $template = sprintf('%s NULLS LAST', $this->template);
            elseif ($this->nullsFirst):
                $template = sprintf('%s NULLS FIRST', $this->template);
            endif;
        endif;
        list($expSql, $expParams) = $compiler->compile($this->expression);
        $params = [
            $expSql,
            ($this->descending) ? 'DESC' : 'ASC',
        ];

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
}
