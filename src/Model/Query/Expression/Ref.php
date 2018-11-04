<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/27/18
 * Time: 6:18 PM.
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
