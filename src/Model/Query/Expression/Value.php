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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class Value extends BaseExpression
{
    /**
     * @var Field
     */
    private $value;

    /**
     * {@inheritdoc}
     */
    public function __construct($value, Field $outputField = null)
    {
        parent::__construct($outputField);
        $this->value = $value;
    }

    /**@inheritdoc */
    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        $val = $this->value;
        if (!is_null($this->outputField)) {
            if (property_exists($this, 'forSave') && $this->forSave) {
                $val = $this->getOutputField()->prepareValueBeforeSave($val, $connection);
            } else {
                $val = $this->getOutputField()->convertToDatabaseValue($val, $connection);
            }
        }
        if (is_null($val)) {
            return ['NULL', []];
        }

        return ['?', [$val]];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveExpression(
        ExpResolverInterface $resolver,
        $allowJoins = true,
        $reuse = null,
        $summarize =
        false,
        $forSave = false
    ) {
        $c = parent::resolveExpression($resolver, $allowJoins, $reuse, $summarize, $forSave);
        $c->forSave = $forSave;

        return $c;
    }

    public function getGroupByCols()
    {
        return [];
    }
}
