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
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class Col extends BaseExpression
{
    private $alias;
    /**
     * @var Field
     */
    private $targetField;

    public function __construct($alias, Field $targetField, Field $outputField = null)
    {
        $this->alias = $alias;
        $this->targetField = $targetField;
        if (is_null($outputField)):
            $outputField = $targetField;
        endif;
        parent::__construct($outputField);
    }

    public static function createObject($alias, Field $targetField, Field $outputField = null)
    {
        return new self($alias, $targetField, $outputField);
    }

    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        return [sprintf('%s.%s', $this->alias, $this->targetField->getColumnName()), []];
    }

    /**
     * @return Field
     */
    public function getTargetField()
    {
        return $this->targetField;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return array
     *
     * @throws \Eddmash\PowerOrm\Exception\FieldError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDbConverters(ConnectionInterface $connection)
    {
        if ($this->getTargetField()->getName() === $this->getOutputField()->getName()):
            return $this->getOutputField()->getDbConverters($connection); else:
            return array_merge(
                $this->getOutputField()->getDbConverters($connection),
                $this->getTargetField()->getDbConverters($connection)
            );
        endif;
    }

    public function getGroupByCols()
    {
        return [$this];
    }
}
