<?php

namespace Eddmash\PowerOrm\Model\Query\Compiler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\QueryException;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Expression\BaseExpression;
use Eddmash\PowerOrm\Model\Query\Expression\Col;
use Eddmash\PowerOrm\Model\Query\Expression\OrderBy;
use Eddmash\PowerOrm\Model\Query\Joinable\BaseJoin;
use Eddmash\PowerOrm\Model\Query\Query;
use const Eddmash\PowerOrm\Model\Query\ORDER_DIRECTION;

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class SqlFetchBaseCompiler extends SqlCompiler
{
    public $klassInfo = [];

    public $annotations = [];

    public $select = [];

    public $where;

    protected function preSqlSetup()
    {
        // check if any of the tables have been used, if not initialize
        $noneUsed = true;
        foreach ($this->query->tablesAliasList as $tablesAlias) :
            if (!empty($this->query->aliasRefCount[$tablesAlias])):
                $noneUsed = false;
            endif;
        endforeach;

        if ($noneUsed):
            $this->query->getInitialAlias();
        endif;

        list($select, $klassInfo, $annotations) = $this->getSelect();
        $this->select = $select;
        $this->klassInfo = $klassInfo;
        $this->annotations = $annotations;

        $orderBy = $this->getOrderBy();
        $groupBy = $this->getGroupBy($this->select, $orderBy);
        $this->where = $this->query->where;

        return [$orderBy, $groupBy];
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        $klassInfo = [];
        $select = [];
        $annotations = [];
        //keeps track of what position the column is at, helpful because of we
        // perform a join we might a column name
        // thats repeated a cross multiple tables, we can use the colmn names
        // to map back to model since it will cause
        // issues
        $selectIDX = 0;
        if ($this->query->useDefaultCols):
            $selectList = [];
            /* @var $field Field */
            foreach ($this->getDefaultCols() as $col) :
                $alias = false;
                $select[] = [$col, $alias];
                $selectList[] = $selectIDX;
                $selectIDX += 1;
            endforeach;
            $klassInfo['model'] = $this->query->model;
            $klassInfo['select_fields'] = $selectList;
        endif;

        // this are used when return the result as array so they are not
        // populated to any model
        foreach ($this->query->select as $col) :
            $alias = false;
            $select[] = [$col, $alias];
            $selectIDX += 1;
        endforeach;

        // handle annotations
        foreach ($this->query->annotations as $alias => $annotation) :
            $annotations[$alias] = $selectIDX;
            $select[] = [$annotation, $alias];
            $selectIDX += 1;
        endforeach;

        // handle select related

        if ($this->query->selectRelected):
            $klassInfo['related_klass_infos'] = $this->getRelatedSelections($select);
            $this->getSelectFromParent($klassInfo);
        endif;

        return [$select, $klassInfo, $annotations];
    }

    public function getOrderBy()
    {
        // an array of arrays that indicate a colname and its a reference
        // e.g. [['name', false]]
        $orderByList = [];
        if (!$this->query->defaultOrdering):
            $ordering = $this->query->orderBy;
        else:
            $ordering = ($this->query->orderBy) ?
                $this->query->orderBy : $this->query->getMeta()->getOrderBy();
        endif;

        if ($this->query->standardOrdering):
            list($asc, $desc) = ORDER_DIRECTION['ASC'];
        else:
            list($asc, $desc) = ORDER_DIRECTION['DESC'];
        endif;

        foreach ($ordering as $orderName) :
            list($colName, $orderDir) = Query::getOrderDirection($orderName, $asc);
            $descending = ('DESC' == $orderDir) ? true : false;

            if ($orderName instanceof BaseExpression):
                $order = $orderName;
                if (!$orderName instanceof OrderBy):
                    $order = $orderName->ascendingOrder();
                endif;
                if (!$this->query->standardOrdering):
                    $order = $order->reverseOrdering();
                endif;
                $orderByList[] = [$order, false];

                continue;
            endif;

            if (array_key_exists($colName, $this->query->annotations)):
                $orderByList[] = [
                    new OrderBy($this->query->annotations[$colName], $descending),
                    false,
                ];

                continue;
            endif;

            // we are here we still have a string name, we need to convert
            // it to an expression that we can use
            if ($colName):
                $orderByList = array_merge(
                    $orderByList,
                    $this->resolveOrderName(
                        $orderName,
                        $this->query->getMeta(),
                        null,
                        $asc
                    )
                );
            endif;
        endforeach;

        $seen = new ArrayCollection();
        $results = [];
        /* @var $orderExp BaseExpression */
        foreach ($orderByList as $orderitem) :
            list($orderExp, $isRef) = $orderitem;
            $resolved = $orderExp->resolveExpression(
                $this->query,
                true
            );
            list($sql, $params) = $this->compile($resolved);

            preg_match(
                "/(?P<name>.*)\s(?P<order>ASC|DESC)(.*)/",
                'demo_entry.id DESC',
                $match
            );
            $strippedSql = $match['name'];

            // ensure we dont add the same field twice
            if ($seen->contains([$strippedSql, $params])):
                continue;
            endif;
            $seen->add([$strippedSql, $params]);
            $results[] = [$resolved, [$sql, $params, $isRef]];
        endforeach;

        return $results;
    }

    public function getGroupBy($select, $orderBy)
    {
        if (is_null($this->query->groupBy)):
            return [];
        endif;

        $expressions = [];
        if (!$this->query->groupBy):
            foreach ($this->query->groupBy as $item) :
                if (!$item instanceof SqlCompilableinterface):
                    //                    $item = $item->re
                    throw new NotImplemented();
                else:
                    $expressions[] = $item;
                endif;
            endforeach;
        endif;
        // Note that even if the group_by is set, it is only the minimal
        // set to group by. So, we need to add cols in select, order_by, and
        // having into the select in any case.

        /* @var $exp BaseExpression */
        foreach ($this->select as $colInfo) :
            list($exp, $alias) = $colInfo;
            foreach ($exp->getGroupByCols() as $groupByCol) :
                $expressions[] = $groupByCol;
            endforeach;
        endforeach;

        foreach ($orderBy as $orderItems) :
            list($exp, $opts) = $orderItems;
            list($sql, $params, $isRef) = $opts;
            if ($exp->containsAggregates()):
                continue;
            endif;
            if ($isRef):
                continue;
            endif;
            $expressions = array_merge($expressions, $exp->getSourceExpressions());
        endforeach;

        $results = [];
        $seen = new ArrayCollection();
        //todo having
        foreach ($expressions as $expression) :
            list($sql, $params) = $this->compile($expression);
            if (!$seen->contains([$sql, $params])):
                $results[] = [$sql, $params];
                $seen[] = [$sql, $params];
            endif;
        endforeach;

        return $results;
    }

    /**     *
     * Returns the fields in the current models/those represented by the alias as Col expression, which know how to be
     * used in a query.
     *
     * @param null       $startAlias
     * @param Meta|null  $meta
     * @param Model|null $fromParent
     *
     * @return Col[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDefaultCols($startAlias = null, Meta $meta = null, $fromParent = null)
    {
        $fields = [];
        if (is_null($meta)):
            $meta = $this->query->getMeta();
        endif;
        if (is_null($startAlias)):
            $startAlias = $this->query->getInitialAlias();
        endif;

        foreach ($meta->getConcreteFields() as $field) :
            $model = $field->scopeModel->getMeta()->getConcreteModel();
            if ($meta->getNSModelName() == $model->getMeta()->getNSModelName()):
                $model = null;
            endif;
            if ($fromParent && !is_null($model) &&
                is_subclass_of(
                    $fromParent->getMeta()->getConcreteModel(),
                    $model->getMeta()->getConcreteModel()->getMeta()->getNSModelName()
                )
            ):
                // Avoid loading data for already loaded parents.
                // We end up here in the case selectRelated() resolution
                // proceeds from parent model to child model. In that case the
                // parent model data is already present in the SELECT clause,
                // and we want to avoid reloading the same data again.
                continue;
            endif;
            //todo if we ever do defer
            $fields[] = $field->getColExpression($startAlias);
        endforeach;

        return $fields;
    }

    /**
     * Used to get information needed when we are doing selectRelated(),.
     *
     * @param           $select
     * @param Meta|null $meta       the from which we expect to find the related fields
     * @param null      $rootAlias
     * @param int       $curDepth
     * @param null      $requested  the set of fields to use in selectRelated
     * @param null      $restricted true when we are to use just a set of relationship fields
     *
     * @return array
     *
     * @throws FieldError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getRelatedSelections(
        &$select,
        Meta $meta = null,
        $rootAlias = null,
        $curDepth = 1,
        $requested = null,
        $restricted = null
    ) {
        $relatedKlassInfo = [];

        if (!$restricted && $this->query->maxDepth && $curDepth > $this->query->maxDepth):
            //We've recursed far enough; bail out.
            return $relatedKlassInfo;
        endif;
        $foundFields = [];
        if (is_null($meta)):
            $meta = $this->query->getMeta();
            $rootAlias = $this->query->getInitialAlias();
        endif;

        if (is_null($requested)):
            if (is_array($this->query->selectRelected)):
                $requested = $this->query->selectRelected;
                $restricted = true;
            else:
                $restricted = false;
            endif;
        endif;

        foreach ($meta->getNonM2MForwardFields() as $field) :
            $fieldModel = $field->scopeModel->getMeta()->getConcreteModel();
            $foundFields[] = $field->getName();

            if ($restricted):
                // first ensure the requested fields are relational
                // and that we are not trying to use a non-relational field
                // we are getting the next field in the spanning relationship i.e author__user so we are getting user.
                // if not a spanning relationship we return an empty array.
                $nextSpanField = ArrayHelper::getValue($requested, $field->getName(), []);
                if (!$field->isRelation):

                    if ($nextSpanField || in_array($field->getName(), $requested)):
                        throw new FieldError(
                            sprintf(
                                "Non-relational field given in selectRelated: '%s'. ".
                                'Choices are: %s',
                                $field->getName(),
                                implode(', ', $this->query->getFieldChoices())
                            )
                        );

                    endif;
                endif;
            else:
                $nextSpanField = false;
            endif;

            if (!static::selectRelatedDescend($field, $restricted, $requested)):
                continue;
            endif;
            $klassInfo = [
                'model' => $field->relation->getToModel(),
                'field' => $field,
                'reverse' => false,
                'from_parent' => false,
            ];

            list($_, $_, $joinList, $_) = $this->query->setupJoins([$field->getName()], $meta, $rootAlias);
            $alias = end($joinList);
            $columns = $this->getDefaultCols($alias, $field->relation->getToModel()->getMeta());
            $selectFields = [];
            foreach ($columns as $column) :
                $selectFields[] = count($select);
                $select[] = [$column, false];
            endforeach;
            $klassInfo['select_fields'] = $selectFields;

            // now go the next field in the spanning relationship i.e. if we have author__user, we just did author
            // so no we do user and so in
            $nextKlassInfo = $this->getRelatedSelections(
                $select,
                $field->relation->getToModel()->getMeta(),
                $alias,
                $curDepth + 1,
                $nextSpanField,
                $restricted
            );
            $klassInfo['related_klass_infos'] = $nextKlassInfo;
            $relatedKlassInfo[] = $klassInfo;

        endforeach;

        if ($restricted):

            $reverseFields = [];
            // we follow back relationship that represent single valuse this most will be relation field that are
            // unique e.g. OneToOneField or ForeignKey with unique set to true.
            // this meas we don't consider m2m fields even if they are unique

            foreach ($meta->getReverseRelatedObjects() as $field) :

                if ($field->unique && !$field->manyToMany):
                    $model = $field->relation->getFromModel();
                    $reverseFields[] = [$field, $model];
                endif;
            endforeach;

            /* @var $rField RelatedField */
            /* @var $rModel Model */
            foreach ($reverseFields as $reverseField) :
                $rField = $reverseField[0];
                $rModel = $reverseField[1];

                if (!$this->selectRelatedDescend($rField, $restricted, $requested, true)):
                    continue;
                endif;
                $relatedFieldName = $rField->getRelatedQueryName();

                $foundFields[] = $relatedFieldName;

                list($_, $_, $joinList, $_) = $this->query->setupJoins([$relatedFieldName], $meta, $rootAlias);
                $alias = end($joinList);
                $fromParent = false;
                if (
                    is_subclass_of($rModel, $meta->getNSModelName()) &&
                    $rModel->getMeta()->getNSModelName() === $meta->getNSModelName()
                ):
                    $fromParent = true;
                endif;

                $rKlassInfo = [
                    'model' => $rModel,
                    'field' => $rField,
                    'reverse' => true,
                    'from_parent' => $fromParent,
                ];

                $rColumns = $this->getDefaultCols($alias, $rModel->getMeta(), $this->query->model);

                $rSelectFields = [];
                foreach ($rColumns as $column) :
                    $selectFields[] = count($select);
                    $select[] = [$column, false];
                endforeach;
                $rKlassInfo['select_fields'] = $rSelectFields;

                $rNextSpanField = ArrayHelper::getValue($requested, $rField->getRelatedQueryName(), []);

                $rNextKlassInfo = $this->getRelatedSelections(
                    $select,
                    $rModel->getMeta(),
                    $alias,
                    $curDepth + 1,
                    $rNextSpanField,
                    $restricted
                );
                $rKlassInfo['related_klass_infos'] = $rNextKlassInfo;

                $relatedKlassInfo[] = $rKlassInfo;
            endforeach;

            $fieldsNotFound = array_diff(array_keys($requested), $foundFields);

            if ($fieldsNotFound):
                throw new FieldError(
                    sprintf(
                        'Invalid field name(s) given in select_related: %s. Choices are: %s',
                        implode(', ', $fieldsNotFound),
                        implode(', ', $this->query->getFieldChoices())
                    )
                );

            endif;
        endif;

        return $relatedKlassInfo;
    }

    /**
     * For each related klass, if the klass extends the model whose info we get, we need to add the models class to
     * the select_fields of the related class.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getSelectFromParent(&$klassInfo)
    {
        foreach ($klassInfo['related_klass_infos'] as &$relatedKlassInfo) :
            // get fields from parent and add them to the related class.
            if ($relatedKlassInfo['from_parent']):

                $relatedKlassInfo['select_fields'] = array_merge(
                    $klassInfo['select_fields'],
                    $relatedKlassInfo['select_fields']
                );
            endif;

            // do the same for the related class fields incase it has own children.
            $this->getSelectFromParent($relatedKlassInfo);
        endforeach;
    }

    public function getFrom()
    {
        $result = [];
        $params = [];

        $refCount = $this->query->aliasRefCount;

        foreach ($this->query->tablesAliasList as $alias) :
            if (!ArrayHelper::getValue($refCount, $alias)):
                continue;
            endif;

            try {
                /** @var $from BaseJoin */
                $from = ArrayHelper::getValue(
                    $this->query->tableAliasMap,
                    $alias,
                    ArrayHelper::STRICT
                );
                list($fromSql, $fromParams) = $this->compile($from);
                array_push($result, $fromSql);
                $params = array_merge($params, $fromParams);
            } catch (KeyError $e) {
                continue;
            }
        endforeach;

        return [$result, $params];
    }

    /**
     * @param bool $chunked
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws QueryException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function executeSql($chunked = false)
    {
        try {
            list($sql, $params) = $this->asSql($this, $this->connection);
            $stmt = $this->connection->prepare($sql);
            foreach ($params as $index => $value) :
                // Columns/Parameters are 1-based,
                // so need to start at 1 instead of zero
                ++$index;
                $stmt->bindValue($index, $value);
            endforeach;

            $stmt->execute();

            return $stmt;
        } catch (\Exception $e) {
            throw QueryException::fromThrowable($e);
        }
    }

    /**
     * Ensure results are converted back to there respective php types.
     *
     * @param $values
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preparedResults($values)
    {
        $preparedValues = [];

        /* @var $col Col */
        /* @var $field Field */
        foreach ($this->select as $pos => $selectColumn) :
            $col = $selectColumn[0];
            $field = $col->getOutputField();
            $val = ArrayHelper::getValue($values, $pos);
            // first use the inbuilt converters
            try {
                $val = Type::getType(
                    $field->dbType($this->connection)
                )->convertToPHPValue($val, $this->connection->getDatabasePlatform());
            } catch (DBALException $exception) {
            }

            // use the field converters if any were provided by the user.
            $converters = $field->getDbConverters($this->connection);

            if ($converters):
                foreach ($converters as $converter) :
                    $val = call_user_func($converter, $this->connection, $val, $field);
                endforeach;
            endif;
            $preparedValues[] = $val;

        endforeach;

        return $preparedValues;
    }

    public function getResultsIterator(Statement $statement)
    {
        // since php pdo normally returns an assoc array, we ask it return the values in form an array indexed
        // by column number as returned in the corresponding result set, starting at column 0.
        // this to avoid issues where joins result in columns with the same name e.g. user.id joined by blog.id
        $results = $statement->fetchAll(\PDO::FETCH_NUM);

        foreach ($results as $row) :
            yield $this->preparedResults($row);
        endforeach;
    }

    /**
     * Creates the SQL for this query. Returns the SQL string and list of parameters.
     *
     * @param CompilerInterface $compiler
     * @param Connection        $connection
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function asSql(
        CompilerInterface $compiler = null,
        ConnectionInterface $connection = null
    ) {
        list($orderBy, $groupBy) = $this->preSqlSetup();
        $params = [];
        list($fromClause, $fromParams) = $this->getFrom();

        $results = ['SELECT'];

        // todo DISTINCT

        $cols = [];

        /* @var $col Col */
        foreach ($this->select as $colInfo) :
            list($col, $alias) = $colInfo;

            list($colSql, $colParams) = $this->compile($col);
            if ($alias):
                $cols[] = sprintf('%s AS %s', $colSql, $alias);
            else:
                $cols[] = $colSql;
            endif;
            $params = array_merge($params, $colParams);
        endforeach;

        $results[] = implode(', ', $cols);

        $results[] = 'FROM';

        $results = array_merge($results, $fromClause);
        $params = array_merge($params, $fromParams);

        if ($this->where):
            list($sql, $whereParams) = $this->compile($this->where);
            if ($sql) :
                $results[] = 'WHERE';
                $results[] = $sql;
                $params = array_merge($params, $whereParams);
            endif;
        endif;

        $grouping = [];
        if ($groupBy):
            foreach ($groupBy as $groupbyItem) :
                list($groupBySql, $groupByParams) = $groupbyItem;
                $grouping[] = $groupBySql;
                $params = array_merge($params, $groupByParams);
            endforeach;
        endif;

        if ($grouping):
            //todo distinct
            $results[] = sprintf('GROUP BY %s', implode(', ', $grouping));
        endif;

        if ($orderBy):
            $ordering = [];
            foreach ($orderBy as $orderByItem) :
                list($_, $opts) = $orderByItem;
                list($orderSql, $orderParams, $_) = $opts;
                $ordering[] = $orderSql;
                $params = array_merge($params, $orderParams);
            endforeach;
            $results[] = sprintf('ORDER BY %s', implode(', ', $ordering));
        endif;

        if ($this->query->limit) :
            $results[] = 'LIMIT';
            $results[] = $this->query->limit;
        endif;

        if (!is_null($this->query->offset)) :
            $results[] = 'OFFSET';
            $results[] = $this->query->offset;
        endif;

        return [implode(' ', $results), $params];
    }

    /**
     * Tries ot resolve a name like 'username' into a model field and then into
     * Col expression suitable for making queries.
     *
     * @param        $col
     * @param Meta   $meta
     * @param null   $alias
     * @param string $defaultOrder
     * @param array  $alreadyResolved helps avoid infinite loops
     *
     * @return array
     *
     * @throws KeyError
     * @throws NotImplemented
     * @throws \Eddmash\PowerOrm\Exception\ValueError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function resolveOrderName(
        $col,
        Meta $meta,
        $alias = null,
        $defaultOrder = 'ASC',
        &$alreadyResolved = []
    ) {
        list($col, $order) = Query::getOrderDirection($col, $defaultOrder);

        $nameParts = explode(BaseLookup::LOOKUP_SEPARATOR, $col);

        /* @var $targetField Field */
        list(
            $relationField, $targetFields, $alias, $joinList, $paths,
            $meta
            ) = $this->_setupJoins($nameParts, $meta, $alias);

        if ($relationField->isRelation && $meta->getOrderBy() &&
            empty($relationField->getAttrName())):
            throw new NotImplemented(
                'This capability is yet to be implemented'
            );
        endif;

        list($targets, $alias, $joinList) = $this->query->trimJoins(
            $targetFields,
            $joinList,
            $paths
        );

        $fields = [];

        $descending = ('DESC' == $order) ? true : false;

        // convert the names to expressions

        /** @var $target Field */
        foreach ($targets as $target):

            $fields[] = [
                new OrderBy($target->getColExpression($alias), $descending),
                false,
            ];

        endforeach;

        return $fields;
    }

    /**
     * A helper method for get_order_by and get_distinct.
     *
     * Note that get_ordering and get_distinct must produce same target
     * columns on same input, as the prefixes of get_ordering and get_distinct
     * must match. Executing SQL where this is not true is an error.
     *
     * @param      $nameParts
     * @param Meta $meta
     * @param null $rootAlias
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @return array
     */
    private function _setupJoins($nameParts, Meta $meta, $rootAlias = null)
    {
        if (is_null($rootAlias)):
            $rootAlias = $this->query->getInitialAlias();
        endif;

        list(
            $field, $targets, $joinList, $paths,
            $meta
            ) = $this->query->setupJoins($nameParts, $meta, $rootAlias);
        $alias = end($joinList);

        return [$field, $targets, $alias, $joinList, $paths, $meta];
    }

    public function hasResults()
    {
        $statement = $this->executeSql();

        return false === empty($statement->fetch());
    }
}
