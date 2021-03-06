<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class Ref extends BaseExpression
{
    protected $name;

    protected $expression;

    /**
     * Ref constructor.
     *
     * @param $name
     * @param $expression
     */
    public function __construct($name, $expression)
    {
        parent::__construct();
        $this->name = $name;
        $this->expression = $expression;
    }

    /**@inheritdoc */
    public function getSourceExpressions()
    {
        return [$this->expression];
    }

    /**@inheritdoc */
    public function setSourceExpressions($expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function asSql(
        CompilerInterface $compiler,
        ConnectionInterface $connection
    ) {
        return [sprintf('%s ', $this->name), []];
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
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupByCols()
    {
        return [$this];
    }
}
