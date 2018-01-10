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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\SqlCompilableinterface;

abstract class BaseExpression extends Combinable
    implements ResolvableExpInterface, SortableInterface, SqlCompilableinterface
{
    /**
     * @var Field
     */
    protected $outputField;

    /**
     * BaseExpression constructor.
     *
     * @param Field $outputField
     */
    public function __construct(Field $outputField = null)
    {
        $this->outputField = $outputField;
    }

    /**
     * Item this expression resolves.
     *
     * @return BaseExpression[]
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getSourceExpressions()
    {
        return [];
    }

    /**
     * Item this expression resolves.
     *
     * @param $expression
     *
     * @return bool
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function setSourceExpressions($expression)
    {
        return assert(0 == count($expression), 'Setting empty expression');
    }

    /**
     * Ensure $expressions is converted into an instances of BaseExpression i.e if we get a string like "age" convert
     * to new F() object or if we get somethin else convert to a new Value() expression.
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @param $expressions
     *
     * @return array
     */
    protected function parseExpression($expressions)
    {
        $args = [];
        foreach ($expressions as $expression) :
            if ($expression instanceof ResolvableExpInterface) :
                $args[] = $expression;
            else:
                if (is_string($expression)) :
                    $args[] = f_($expression);
                else:
                    $args[] = value_($expression);
                endif;
            endif;
        endforeach;

        return $args;
    }

    /**
     * {@inheritdoc}
     */
    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        throw new NotImplemented('Subclasses must implement asSql()');
    }

    public function getLookup($lookup)
    {
        return $this->outputField->getLookup($lookup);
    }

    /**
     * @return Field
     *
     * @throws FieldError
     */
    public function getOutputField()
    {
        if (is_null($this->outputFieldOrNull())) :
            throw new FieldError('Cannot resolve expression type, unknown output_field');
        endif;

        return $this->outputFieldOrNull();
    }

    private function outputFieldOrNull()
    {
        if (is_null($this->resolveOutputField())) :
            $this->resolveOutputField();
        endif;

        return $this->outputField;
    }

    /**Attempts to infer the output type of the expression. If the output fields of all source fields match then we
     * can simply infer the same type here. This isn't always correct, but it makes sense most of the time.
     *
     * Consider the difference between `2 + 2` and `2 / 3`. Inferring the type here is a convenience for the common
     * case.  The user should supply their own outputField with more complex computations.
     *
     *  If a source does not have an `_outputField` then we exclude it from this check. If all sources are `null`,
     * then an error will be thrown higher up the stack in the `outputField` property.
     *
     * @throws FieldError
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    private function resolveOutputField()
    {
        if (is_null($this->outputField)) :
            $sourceFields = $this->getSourceFields();
            if (0 == count($sourceFields)) :
                $this->outputField = null;
            else:
                foreach ($sourceFields as $sourceField) :
                    if (is_null($this->outputField)) :
                        $this->outputField = $sourceField;
                    endif;
                    if (!is_null($this->outputField) && !($this->outputField instanceof $sourceField)) :
                        throw new FieldError('Expression contains mixed types. You must set output_field');
                    endif;
                endforeach;
            endif;
        endif;
    }

    /**
     * Hook used by Lookup.prepareLookup() to do custom preparation.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _prepare()
    {
        return $this;
    }

    /**
     * Provides the chance to do any preprocessing or validation before being added to the query.e.g.
     * in Exp::Count('username') we need the username to converted to an actual model field.
     *
     * @param ExpResolverInterface $resolver
     * @param bool                 $allowJoins
     * @param null                 $reuse
     * @param bool                 $summarize
     * @param bool                 $forSave
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @return $this
     */
    public function resolveExpression(
        ExpResolverInterface $resolver,
        $allowJoins = true,
        $reuse = null,
        $summarize = false,
        $forSave = false
    ) {
        $obj = clone $this;
        $obj->copied = true;

        return $obj;
    }

    private function getSourceFields()
    {
        $fields = [];
        foreach ($this->getSourceExpressions() as $sourceExpression) :
            $fields[] = $sourceExpression->outputFieldOrNull();
        endforeach;

        return $fields;
    }

    public function containsAggregates()
    {
        foreach ($this->getSourceExpressions() as $sourceExpression) :
            if ($sourceExpression->containsAggregates()):
                return true;
            endif;
        endforeach;

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo()
    {
        return [
        ];
    }

    /**
     * how to Sort this expression in descending order.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function descendingOrder($kwargs = [])
    {
        return new OrderBy($this, false, $kwargs);
    }

    /**
     * how to Sort expression in ascending order.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function ascendingOrder($kwargs = [])
    {
        return new OrderBy($this, true, $kwargs);
    }

    /**
     * how to do reverse the current sorting order.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reverseOrdering()
    {
        return $this;
    }

    /**
     * Retuns the fields to be used when this expression is used in a group by.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getGroupByCols()
    {
        if (!$this->containsAggregates()):
            return [$this];
        endif;
        $cols = [];
        foreach ($this->getSourceExpressions() as $sourceExpression) :
            $cols[] = $sourceExpression->getGroupByCols();
        endforeach;

        return $cols;
    }
}
