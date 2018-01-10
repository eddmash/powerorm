<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query;

use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Model\Query\Compiler\SQLUpdateCompiler;

/**
 * Represents an "update" SQL query.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class UpdateQuery extends Query
{
    protected $values = [];

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @throws FieldError
     */
    public function addUpdateValues($values)
    {
        $valuesSeq = [];
        foreach ($values as $name => $value) :
            $field = $this->model->getMeta()->getField($name);
            $model = $field->scopeModel->getMeta()->concreteModel;
            $isDirect = (!($field->autoCreated && !$field->concrete) || !$field->concrete);
            if (!$isDirect || ($field->isRelation && $field->manyToMany)):
                throw new  FieldError(
                    sprintf(
                        'Cannot update model field %r (only non-relations and '.
                        'foreign keys permitted).',
                        $field
                    )
                );
            endif;
            if ($model->getMeta()->getModelName() !== $this->model->getMeta()->getModelName()):
                $this->addRelatedUpdate($model, $field, $value);

                continue;
            endif;
            $valuesSeq[] = [$field, $model, $value];
        endforeach;

        return $this->addUpdateFields($valuesSeq);
    }

    private function addRelatedUpdate($model, $field, $value)
    {
    }

    public function addUpdateFields($valuesSeq)
    {
        foreach ($valuesSeq as $item) {
            $field = $item[0];
            $model = $item[1];
            $value = $item[2];

            // todo handle resolve_expression
            $this->values[] = [$field, $model, $value];
        }
    }

    protected function getCompilerClass()
    {
        return SQLUpdateCompiler::class;
    }
}
