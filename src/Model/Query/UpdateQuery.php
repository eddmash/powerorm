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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;

/**
 * Represents an "update" SQL query.
 *
 * @since 1.1.0
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
            $field = $this->model->meta->getField($name);
            $model = $field->scopeModel->meta->concreteModel;
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
            if ($model->meta->getModelName() !== $this->model->meta->getModelName()):
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

    /**
     * {@inheritdoc}
     */
    public function asSql(Connection $connection, $isSubQuery = false)
    {
        $qb = $connection->createQueryBuilder();
        $qb->update($this->tablesAliasList[0]);
        $params = [];

        /* @var $field Field */
        /* @var $model Model */
        foreach ($this->values as $valItem) :
            $field = $valItem[0];
            $model = $valItem[1];
            $value = $valItem[2];

            $name = $field->getColumnName();
            $qb->set($name, '?');

            //todo resolve_expression,
            if (method_exists($value, 'prepareDatabaseSave')):
                if ($field->isRelation):
                    $value = $field->prepareValueBeforeSave(
                        $value->prepareDatabaseSave($field),
                        $connection
                    );
                else:
                    throw new TypeError(
                        "Tried to update field '%s' with a model instance, '%s'. Use a value compatible with '%s'.",
                        $field->getName(),
                        $value,
                        get_class($field)
                    );

                endif;

            else:
                $value = $field->prepareValueBeforeSave($value, $connection);
            endif;
            // prepare value
            $params[] = $value;
        endforeach;

        list($sql, $whereParams) = $this->where->asSql($connection);
        $qb->where($sql);
        $params = array_merge($params, $whereParams);

        foreach ($params as $index => $param) :
            $qb->setParameter($index, $param);
        endforeach;

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        $qb = $this->asSql($connection);

        return (bool) $qb->execute();
    }

}
