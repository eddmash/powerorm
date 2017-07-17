<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Lookup\LookupInterface;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Aggregates\BaseAggregate;
use Eddmash\PowerOrm\Model\Query\Expression\BaseExpression;
use Eddmash\PowerOrm\Model\Query\Expression\Col;
use Eddmash\PowerOrm\Model\Query\Expression\Exp;
use Eddmash\PowerOrm\Model\Query\Joinable\BaseJoin;
use Eddmash\PowerOrm\Model\Query\Joinable\BaseTable;
use Eddmash\PowerOrm\Model\Query\Joinable\Join;
use Eddmash\PowerOrm\Model\Query\Joinable\Where;

const INNER = 'INNER JOIN';
const LOUTER = 'LEFT OUTER JOIN';

class Query extends BaseObject
{
    //[
    //  BaseLookup::AND_CONNECTOR => [],
    //  BaseLookup::OR_CONNECTOR => [],
    //];
    public $offset;
    public $limit;

    /** @var Where */
    public $where;
    public $tables = [];
    public $tableMap = [];
    public $selectRelected = false;
    /**
     * @var BaseJoin[]
     */
    public $tableAlias = [];
    public $aliasRefCount = [];

    /**
     * @var Col[]
     */
    public $select = [];
    public $valueSelect = [];
    public $klassInfo;
    public $isSubQuery = false;

    /**
     * @var Model
     */
    public $model;

    /**
     * if true, get the columns to fetch from the model itself.
     *
     * @var
     */
    public $useDefaultCols = true;

    /**
     * @var BaseAggregate[]
     */
    public $annotations = [];
    public $distict;
    public $distictFields = [];
    public $orderBy;
    public $defaultOrdering = [];

    // Arbitrary limit for select_related to prevents infinite recursion.
    public $maxDepth = 5;
    public $columnInfoCache;

    /**
     * Query constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model, $where = null)
    {
        $this->model = $model;
        if (is_null($where)) :
            $this->where = Where::createObject();
        else:
            $this->where = $where;
        endif;

    }

    public static function createObject(Model $model)
    {
        return new self($model);
    }

    private function preSqlSetup()
    {
        if (!$this->tables):
            $this->getInitialAlias();
        endif;

        list($select, $klassInfo) = $this->getSelect();
        $this->select = $select;
        $this->klassInfo = $klassInfo;
    }

    /**
     * Creates the SQL for this query. Returns the SQL string and list of parameters.
     *
     * @param Connection $connection
     * @param bool       $isSubQuery
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function asSql(Connection $connection, $isSubQuery = false)
    {
        $this->isSubQuery = $isSubQuery;
        $this->preSqlSetup();
        $params = [];
        list($fromClause, $fromParams) = $this->getFrom($connection);

        $results = ['SELECT'];

        // todo DISTINCT

        $cols = [];

        /* @var $col Col */
        foreach ($this->select as $colInfo) :
            list($col, $alias) = $colInfo;

            list($colSql, $colParams) = $col->asSql($connection);
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
            list($sql, $whereParams) = $this->where->asSql($connection);
            if ($sql) :
                $results[] = 'WHERE';
                $results[] = $sql;
                $params = array_merge($params, $whereParams);
            endif;
        endif;

        if ($this->limit) :
            $results[] = 'LIMIT';
            $results[] = $this->limit;
        endif;

        if ($this->offset) :
            $results[] = 'OFFSET';
            $results[] = $this->offset;
        endif;

        return [implode(' ', $results), $params];
    }

    public function getNestedSql(Connection $connection)
    {

        return $this->asSql($connection, true);
    }

    public function addSelect(Col $col)
    {
        $this->useDefaultCols = false;
        $this->select[] = $col;
    }

    public function addFields($fieldNames, $allowM2M = true)
    {
        $alias = $this->getInitialAlias();
        $meta = $this->model->meta;

        foreach ($fieldNames as $fieldName) :
            $names = StringHelper::split(BaseLookup::$lookupPattern, $fieldName);
            list($field, $targets, $joinList, $paths) = $this->setupJoins($names, $meta, $alias);

            /** @var $targets Field[] */
            list($targets, $finalAlias, $joinList) = $this->trimJoins($targets, $joinList, $paths);

            foreach ($targets as $target) :
                $this->addSelect($target->getColExpression($finalAlias));
            endforeach;

        endforeach;

    }

    public function clearSelectedFields()
    {
        $this->select = [];
        $this->valueSelect = [];
    }

    /**
     * @return array
     */
    public function getSelect()
    {

        $klassInfo = [];
        $select = [];
        $annotations = [];
        //keeps track of what position the column is at helpful because of we perform a join we might a column name
        // thats repeated a cross multiple tables, we can use the colmn names to map back to model since it will cause
        // issues
        $selectIDX = 0;
        if ($this->useDefaultCols):
            $selectList = [];
            /* @var $field Field */
            foreach ($this->getDefaultCols() as $col) :
                $alias = false;
                $select[] = [$col, $alias];
                $selectList[] = $selectIDX;
                $selectIDX += 1;
            endforeach;
            $klassInfo['model'] = $this->model;
            $klassInfo['select_fields'] = $selectList;
        endif;

        // this are used when return the result as array so they are not populated to any model
        foreach ($this->select as $col) :
            $alias = false;
            $select[] = [$col, $alias];
            $selectIDX += 1;
        endforeach;

        // handle annotations
        foreach ($this->annotations as $alias => $annotation) :
            $annotations[$alias] = $selectIDX;
            $select[] = [$annotation, $alias];
            $selectIDX += 1;
        endforeach;

        // handle select related

        if ($this->selectRelected):
            $klassInfo['related_klass_infos'] = $this->getRelatedSelections($select);
            $this->getSelectFromParent($klassInfo);
        endif;

        return [$select, $klassInfo, $annotations];
    }

    /**
     * Used to get information needed when we are doing selectRelated(),.
     *
     * @param $select
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getRelatedSelections(&$select, Meta $meta = null,
                                          $rootAlias = null,
                                          $curDepth = 1,
                                          $requested = null,
                                          $restricted = null)
    {
        $relatedKlassInfo = [];

        if (!$restricted && $this->maxDepth && $curDepth > $this->maxDepth):
            //We've recursed far enough; bail out.
            return $relatedKlassInfo;
        endif;
        $foundFields = [];
        if (is_null($meta)):
            $meta = $this->model->meta;
            $rootAlias = $this->getInitialAlias();
        endif;

        if (is_null($requested)):
            if (is_array($this->selectRelected)):
                $requested = $this->selectRelected;
                $restricted = true;
            else:
                $restricted = false;
            endif;
        endif;

        foreach ($meta->getNonM2MForwardFields() as $field) :
            $fieldModel = $field->scopeModel->meta->concreteModel;
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
                            sprintf("Non-relational field given in selectRelated: '%s'. ".
                                'Choices are: %s', $field->getName(), implode(', ', $this->getFieldChoices())));

                    endif;
                endif;
            else:
                $nextSpanField = false;
            endif;

            if (!$this->selectRelatedDescend($field, $restricted, $requested)):
                continue;
            endif;
            $klassInfo = [
                'model' => $field->relation->getToModel(),
                'field' => $field,
                'reverse' => false,
                'from_parent' => false,
            ];

            list($_, $_, $joinList, $_) = $this->setupJoins([$field->getName()], $meta, $rootAlias);
            $alias = end($joinList);
            $columns = $this->getDefaultCols($alias, $field->relation->getToModel()->meta);
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
                $field->relation->getToModel()->meta,
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

                list($_, $_, $joinList, $_) = $this->setupJoins([$relatedFieldName], $meta, $rootAlias);
                $alias = end($joinList);
                $fromParent = false;
                if (
                    is_subclass_of($rModel, $meta->getNamespacedModelName()) &&
                    $rModel->meta->getNamespacedModelName() === $meta->getNamespacedModelName()
                ):
                    $fromParent = true;
                endif;

                $rKlassInfo = [
                    'model' => $rModel,
                    'field' => $rField,
                    'reverse' => true,
                    'from_parent' => $fromParent,
                ];

                $rColumns = $this->getDefaultCols($alias, $rModel->meta, $this->model);

                $rSelectFields = [];
                foreach ($rColumns as $column) :
                    $selectFields[] = count($select);
                    $select[] = [$column, false];
                endforeach;
                $rKlassInfo['select_fields'] = $rSelectFields;

                $rNextSpanField = ArrayHelper::getValue($requested, $rField->getRelatedQueryName(), []);

                $rNextKlassInfo = $this->getRelatedSelections(
                    $select,
                    $rModel->meta,
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
                    sprintf('Invalid field name(s) given in select_related: %s. Choices are: %s',
                        implode(', ', $fieldsNotFound), implode(', ', $this->getFieldChoices())));

            endif;
        endif;

        return $relatedKlassInfo;
    }

    /**
     * For each related klass, if the klass extends the model whose info we get, we need to add the models class to
     * the select_fields of the related class.
     *
     * @since 1.1.0
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDefaultCols($startAlias = null, Meta $meta = null, $fromParent = null)
    {

        $fields = [];
        if (is_null($meta)):
            $meta = $this->model->meta;
        endif;
        if (is_null($startAlias)):
            $startAlias = $this->getInitialAlias();
        endif;

        foreach ($meta->getConcreteFields() as $field) :
            $model = $field->scopeModel->meta->concreteModel;
            if ($meta->getNamespacedModelName() == $model->meta->getNamespacedModelName()):
                $model = null;
            endif;
            if ($fromParent && !is_null($model) &&
                is_subclass_of($fromParent->meta->concreteModel,
                    $model->meta->concreteModel->meta->getNamespacedModelName())
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

    public function getFrom(Connection $connection)
    {
        $result = [];
        $params = [];

        $refCount = $this->aliasRefCount;

        foreach ($this->tables as $alias) :
            if (!ArrayHelper::getValue($refCount, $alias)):
                continue;
            endif;
            try {

                /** @var $from BaseJoin */
                $from = ArrayHelper::getValue($this->tableMap, $alias, ArrayHelper::STRICT);

                list($fromSql, $fromParams) = $from->asSql($connection);
                array_push($result, $fromSql);
                $params = array_merge($params, $fromParams);
            } catch (KeyError $e) {
                continue;
            }
        endforeach;

        return [$result, []];
    }

    /**
     * @return Where
     */
    public function getWhere()
    {
        return $this->where;
    }

    public function addConditions($condition, $negate)
    {
        $this->buildFilter($condition, $negate);
    }

    /**
     * @param $condition
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildFilter($conditions, $negate = false)
    {
        //todo $negate
        $alias = $this->getInitialAlias();

        /* @var $targets Field[] */
        /* @var $field Field */

        foreach ($conditions as $name => $value) :
            list($connector, $lookups, $fieldParts) = $this->solveLookupType($name);
            list($value, $lookups) = $this->prepareLookupValue($value, $lookups);

            list($field, $targets, $joinList, $paths) = $this->setupJoins(
                $fieldParts,
                $this->model->meta,
                $alias
            );

            list($targets, $alias, $joinList) = $this->trimJoins($targets, $joinList, $paths);

//            if ($field->isRelation) :
//                $lookupClass = $field->getLookup($lookups[0]);
//                $col = $targets[0]->getColExpression($alias, $field);
//                $condition = $lookupClass::createObject($col, $value);
//            else:
//                $col = $targets[0]->getColExpression($alias, $field);
//                $condition = $this->buildCondition($lookups, $col, $value);
//            endif;

//            $this->where->setConditions($connector, $condition);

        endforeach;
    }

    /**
     * @param array $valueSelect
     */
    public function setValueSelect($valueSelect)
    {
        $this->valueSelect[] = $valueSelect;
    }

    private function checkRelatedObjects(Field $field, $value, Meta $meta)
    {
        //todo
    }

    /**
     * @param $lookup
     * @param $rhs
     * @param $lhs
     *
     * @return LookupInterface
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildCondition($lookup, $lhs, $rhs)
    {
        $lookup = (array) $lookup;

        $lookup = $lhs->getLookup($lookup[0]);
        /* @var $lookup LookupInterface */
        $lookup = $lookup::createObject($lhs, $rhs);

        return $lookup;
    }

    private function solveLookupType($name)
    {
        list($connector, $names) = $this->getConnector($name);
        // get lookupand field
        $split_names = StringHelper::split(BaseLookup::$lookupPattern, $names);

        $paths = $this->getNamesPath($split_names, $this->model->meta);
        $lookup = $paths['others'];

        $fieldParts = [];
        foreach ($split_names as $name) :
            if (in_array($name, $lookup)) :
                continue;
            endif;
            $fieldParts[] = $name;
        endforeach;

        if (count($lookup) === 0) :
            $lookup[] = 'exact';
        elseif (count($fieldParts) > 1):
            if (!$fieldParts) :
                throw new FieldError(
                    sprintf(
                        'Invalid lookup "%s" for model %s".',
                        $names,
                        $this->model->meta->getNamespacedModelName()
                    )
                );
            endif;
        endif;

        return [$connector, $lookup, $fieldParts];
    }

    private function prepareLookupValue($value, $lookups)
    {
        if (empty($lookups)):
            $lookups = ['exact'];
        endif;

        // Interpret '__exact=None' as the sql 'is NULL'; otherwise, reject all
        // uses of null as a query value.
        if (is_null($value)):
            if (!in_array(array_pop($lookups), ['exact'])):
                throw new ValueError('Cannot use None as a query value');
            endif;

            return [true, ['isnull']];
        endif;

        return [$value, $lookups];
    }

    /**
     * @param $names
     * @param Meta $meta
     * @param bool $failOnMissing
     *
     * @return array
     *
     * @throws FieldError
     */
    public function getNamesPath($names, Meta $meta, $failOnMissing = false)
    {
        $paths = $targets = [];
        $finalField = null;

        $posReached = 0;

        /* @var $field Field|RelatedField|ForeignObjectRel */
        foreach ($names as $pos => $name) :

            $posReached = $pos;

            if ($name === PRIMARY_KEY_ID):
                $name = $meta->primaryKey->getName();
            endif;

            $field = null;

            try {
                $field = $meta->getField($name);
            } catch (FieldDoesNotExist $e) {
                //todo check in annotations to
                $available = getFieldNamesFromMeta($meta);
                if ($failOnMissing) :

                    throw new FieldError(
                        sprintf(
                            "Cannot resolve keyword '%s.%s' into field. Choices are: [ %s ]",
                            $meta->getNamespacedModelName(),
                            $name,
                            implode(', ', $available)
                        )
                    );
                else:
                    break;
                endif;
            }

            // todo Check if we need any joins for concrete inheritance cases (the
            // field lives in parent, but we are currently in one of its
            // children)

            if ($field->hasMethod('getPathInfo')) :

                $pathsInfos = $field->getPathInfo();

                $last = $pathsInfos[count($pathsInfos) - 1];
                $finalField = ArrayHelper::getValue($last, 'joinField');
                $targets = ArrayHelper::getValue($last, 'targetFields');
                $meta = ArrayHelper::getValue($last, 'toMeta');
                $paths = array_merge($paths, $pathsInfos);
            else:
                // none relational field
                $finalField = null;
                $finalField = $field;

                $targets[] = $field;
                // no need to go on since this is a none relation field.
                break;
            endif;
        endforeach;

        return [
            'paths' => $paths,
            'finalField' => $finalField,
            'targets' => $targets,
            'others' => array_slice($names, $posReached + 1),
        ];
    }

    /**
     * Determines the where clause connector to use.
     *
     * @param $name
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getConnector($name)
    {
        $connector = BaseLookup::AND_CONNECTOR;

        // get the actual key
        if (preg_match(BaseLookup::$whereConcatPattern, $name)):
            // determine how to combine where statements
            list($lookup, $name) = preg_split(BaseLookup::$whereConcatPattern, $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $connector = BaseLookup::OR_CONNECTOR;
        endif;

        return [$connector, $name];
    }

    public function setLimit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function getInitialAlias()
    {
        if ($this->tables):
            // get the first one
            $alias = $this->tables[0];
        else:
            $alias = $this->join(new BaseTable($this->model->meta->dbTable, null));
        endif;

        return $alias;
    }

    private function setupJoins($names, Meta $meta, $alias)
    {
        $joins = [$alias];

        $namesPaths = $this->getNamesPath($names, $meta, true);
        $pathInfos = $namesPaths['paths'];

        /* @var $meta Meta */
        foreach ($pathInfos as $pathInfo) :
            $meta = $pathInfo['toMeta'];

            if ($pathInfo['direct']):
                $nullable = $this->isNullable($pathInfo['joinField']);
            else:
                $nullable = true;
            endif;
            $join = new Join();
            $join->setTableName($meta->dbTable);
            $join->setParentAlias($alias);
            $join->setJoinType(INNER);
            $join->setJoinField($pathInfo['joinField']);
            $join->setNullable($nullable);

            $alias = $this->join($join);

            $joins[] = $alias;
        endforeach;

        return [$namesPaths['finalField'], $namesPaths['targets'], $joins, $pathInfos];
    }

    public function join(BaseJoin $join, $reuse = [])
    {
        list($alias) = $this->getTableAlias($join->getTableName(), false);
        if ($join->getJoinType()):
            if ($this->tableMap[$join->getParentAlias()]->getJoinType() === LOUTER || $join->getNullable()):

                $joinType = LOUTER;
            else:
                $joinType = INNER;
            endif;
            $join->setJoinType($joinType);
        endif;

        $join->setTableAlias($alias);
        $this->tableMap[$alias] = $join;
        $this->tables[] = $alias;

        return $alias;
    }

    private function trimJoins($targets, $joinList, $path)
    {
        /* @var $joinField RelatedField */
        /* @var $field Field */
        /* @var $relField Field[] */

        foreach (array_reverse($path) as $info) :
            if (!$info['direct'] || count($joinList) === 1):
                break;
            endif;

            $joinTargets = [];
            $currentTargets = [];
            $joinField = $info['joinField'];

            foreach ($joinField->getForeignRelatedFields() as $field) :
                $joinTargets[] = $field->getColumnName();
            endforeach;

            foreach ($targets as $field) :
                $currentTargets[] = $field->getColumnName();
            endforeach;

            if (!array_intersect($joinTargets, $currentTargets)):
                break;
            endif;

            $relFields = [$joinField->getRelatedFields()];
            $relMap = [];
            foreach ($relFields as $relField) :
                if (in_array($relField[1]->getColumnName(), $currentTargets)):
                    $relMap[$relField[1]->getColumnName()] = $relField[0];
                endif;
            endforeach;

            dump($targets);
            dump($relMap);
            dump($currentTargets);
            $targetsNew = [];
            foreach ($targets as $target) :
//                $targetsNew[] = $relMap[$target->getColumnName()];
            endforeach;
            $targets = $targetsNew;

            $this->unrefAlias(array_pop($joinList));
        endforeach;

        $alias = array_slice($joinList, -1)[0];

        return [$targets, $alias, $joinList];
    }

    private function unrefAlias($alias, $amount = 1)
    {
        $this->aliasRefCount[$alias] -= $amount;
    }

    public function getTableAlias($tableName, $create = false)
    {
        if (ArrayHelper::hasKey($this->tableAlias, $tableName) && false === $create):
            $alias = ArrayHelper::getValue($this->tableAlias, $tableName);
            $this->aliasRefCount[$alias] += 1;

            return [$alias, false];
        endif;

        $alias = $tableName;
        $this->tableAlias[$alias] = $alias;
        $this->aliasRefCount[$alias] = 1;

        return [$alias, true];
    }

    public function addAnnotation($kwargs = [])
    {
        /** @var $annotation BaseExpression */
        $annotation = ArrayHelper::getValue($kwargs, 'annotation');
        $alias = ArrayHelper::getValue($kwargs, 'alias');
        $isSummary = ArrayHelper::getValue($kwargs, 'isSummary', false);
//        $annotation = $annotation->resolveExpression();
        $this->annotations[$alias] = $annotation;

    }

    /**Sets up the select_related data structure so that we only select certain related models
     * (as opposed to all models, when self.select_related=True)
     *
     * @param array $fields
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addSelectRelected($fields = [])
    {
        if (is_bool($this->selectRelected)):
            $relatedFields = [];
        else:
            $relatedFields = $this->selectRelected;
        endif;

        foreach ($fields as $field) :
            $names = StringHelper::split(BaseLookup::$lookupPattern, $field);
            // we use by reference so that we assigned the values back to the original array
            $d = &$relatedFields;
            foreach ($names as $name) :
                $d = &$d[$name];
                if (empty($d)):
                    $d = [];
                endif;
            endforeach;
        endforeach;
        $this->selectRelected = $relatedFields;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getAggregation(Connection $connection, $addedAggregateNames = [])
    {
        if (!$this->annotations):
            return [];
        endif;
        $hasLimit = ($this->offset || $this->limit);
        $hasExistingAnnotations = false;
        foreach ($this->annotations as $alias => $annotation) :

            $hasExistingAnnotations = ($hasExistingAnnotations || !in_array($alias, $addedAggregateNames));
        endforeach;

        // we have of this we need to make the core query a subquery and aggregate over it.
        if ($hasExistingAnnotations || $hasLimit || $this->distict):
            $outQuery = new AggregateQuery($this->model);
            $innerQuery = $this->deepClone();

            $innerQuery->selectRelected = false;

            if (!$hasLimit && !$this->distictFields):
                // Queries with distinct_fields need ordering and when a limit
                // is applied we must take the slice from the ordered query.
                // Otherwise no need for ordering, so clear.
                $innerQuery->clearOrdering(true);
            endif;

            if (!$innerQuery->distict):
                // if we are using default columns and we already have aggregate annotations existing
                // then we must make sure the inner
                // query is grouped by the main model's primary key. However,
                // clearing the select clause can alter results if distinct is
                // used.
                if ($innerQuery->useDefaultCols && $hasExistingAnnotations):
                    $innerQuery->groupBy = [
                        $this->model->meta->primaryKey->getColExpression($innerQuery->getInitialAlias()),
                    ];
                endif;
                $innerQuery->useDefaultCols = false;
            endif;

            // add annotations to the outerquery todo
            foreach ($this->annotations as $alias => $annotation) :
                $outQuery->annotations[$alias] = $annotation;
                unset($innerQuery->annotations[$alias]);
            endforeach;

            if ($innerQuery->select == [] && !$innerQuery->useDefaultCols):
                $innerQuery->select = [
                    $this->model->meta->primaryKey->getColExpression(
                        $innerQuery->getInitialAlias()
                    ),
                ];
            endif;

            $outQuery->addSubQuery($innerQuery, $connection);
        else:
            $outQuery = $this;
            $outQuery->select = [];
            $outQuery->useDefaultCols = false;
        endif;

        $outQuery->clearOrdering(true);
        $outQuery->clearLimits();
        $outQuery->selectRelected = false;

        $results = $outQuery->execute($connection)->fetch();

        $result = [];
        foreach (array_combine(array_keys($this->annotations), array_values($results)) as $key => $item) {
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * Removes any ordering settings. If 'force_empty' is True, there will be no
     * ordering in the resulting query (not even the model's default).
     *
     * @param bool $forceEmpty
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function clearOrdering($forceEmpty = false)
    {
        $this->orderBy = false;
        if ($forceEmpty):
            $this->defaultOrdering = false;
        endif;
    }

    /**
     * Clears any existing limits.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function clearLimits()
    {
        $this->limit = $this->offset = null;
    }

    public function getCount(Connection $connection)
    {
        $obj = $this->deepClone();
        $obj->addAnnotation(['annotation' => Exp::Count('*'), 'alias' => '_count', 'isSummary' => true]);
        $alias = '_count';
        $result = $obj->getAggregation($connection, [$alias]);

        return ArrayHelper::getValue($result, $alias, 0);
    }

    /**
     * @param string $class
     *
     * @return $this
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deepClone($class = null)
    {

        $class = (is_null($class)) ? static::class : $class;
        $obj = new $class($this->model);
        $obj->aliasRefCount = $this->aliasRefCount;
        $obj->useDefaultCols = $this->useDefaultCols;
        $obj->tableAlias = $this->tableAlias;
        $obj->tableMap = $this->tableMap;
        $obj->tables = $this->tables;
        $obj->select = $this->select;
        $obj->selectRelected = $this->selectRelected;
        $obj->annotations = $this->annotations;
        $obj->offset = $this->offset;
        $obj->limit = $this->limit;
        $obj->where = $this->where->deepClone();

        return $obj;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function execute(Connection $connection)
    {
        list($sql, $params) = $this->asSql($connection);

        $stmt = $connection->prepare($sql);
        foreach ($params as $index => $value) :
            ++$index; // Columns/Parameters are 1-based, so need to start at 1 instead of zero
            $stmt->bindValue($index, $value);
        endforeach;

        $stmt->execute();

        return $stmt;
    }

    public function getResultsIterator(Connection $connection)
    {
        $preparedResults = [];

        // since php pdo normally returns an assoc array, we ask it return the values in form an array indexed
        // by column number as returned in the corresponding result set, starting at column 0.
        // this to avoid issues where joins result in columns with the same name e.g. user.id joined by blog.id
        $results = $this->execute($connection)->fetchAll(\PDO::FETCH_NUM);
        foreach ($results as $row) :
            $preparedResults[] = $this->preparedResults($connection, $row);
        endforeach;

        return $preparedResults;
    }

    /**
     * Gets all names for the fields in the query model, inclusing reverse fields.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getFieldChoices()
    {
        $fields = [];
        foreach ($this->model->meta->getFields() as $field) :
            if (!$field->isRelation):
                continue;
            endif;
            $fields[] = $field->getName();
        endforeach;

        foreach ($this->model->meta->getReverseRelatedObjects() as $reverseRelatedObject) :
            if ($reverseRelatedObject->relation->fromField->isUnique()):
                $fields[] = $reverseRelatedObject->relation->fromField->getRelatedQueryName();
            endif;
        endforeach;

        return $fields;
    }

    /**
     * Returns True if this field should be used to descend deeper for selectRelated() purposes.
     *
     * @param Field $field      the field to be checked
     * @param bool  $restricted indicating if the field list has been manually restricted using a requested clause
     * @param array $requested  The selectRelated() array
     * @param bool  $reverse    True if we are checking a reverse select related
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function selectRelatedDescend(Field $field, $restricted, $requested, $reverse = false)
    {
        if (!$field->relation):
            return false;
        endif;

        if ($field->relation->parentLink && !$reverse):
            return false;
        endif;

        if ($restricted):
            if ($reverse && !array_key_exists($field->getRelatedQueryName(), $requested)):
                return false;
            endif;
            if (!$reverse && !array_key_exists($field->getName(), $requested)):
                return false;
            endif;
        endif;

        if (!$restricted && $field->isNull()):
            return false;
        endif;

        return true;
    }

    /**
     * We check if a field is nullable.
     *
     * @param Field $joinField
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @return bool
     */
    private function isNullable($joinField)
    {
        return $joinField->isNull();
    }

    /**
     * Ensure results are converted back to there respective php types.
     *
     * @param $values
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preparedResults(Connection $connection, $values)
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
                    $field->dbType($connection))->convertToPHPValue($val, $connection->getDatabasePlatform());
            } catch (DBALException $exception) {
            }

            // use the field converters if any were provided by the user.
            $converters = $field->getDbConverters($connection);

            if ($converters):
                foreach ($converters as $converter) :
                    $val = call_user_func($converter, $connection, $val, $field);
                endforeach;
            endif;
            $preparedValues[] = $val;

        endforeach;

        return $preparedValues;
    }

}

/**
 * @param Model[]        $instances
 * @param Prefetch|array $lookups
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function prefetchRelatedObjects($instances, $lookups)
{
    if (!$lookups instanceof Prefetch):
        $msg = sprintf("method '%s()' expects parameter 'lookup' to be an array", __FUNCTION__);
        Tools::ensureParamIsArray($lookups, $msg);
    endif;

    if (count($instances) == 0):
        return;
    endif;

    //We need to be able to dynamically add to the list of prefetch_related
    //lookups that we look up (see below).  So we need some book keeping to
    //ensure we don't do duplicate work.
    $doneQueries = [];  // assoc_array of things like 'foo__bar': [results]
    $lookups = normalizePrefetchLookup($lookups);

    /* @var $lookup Prefetch */
    while ($lookups):
        $lookup = array_shift($lookups);

        // have already worked on a lookup that has similar name
        if (array_key_exists($lookup->prefetchTo, $doneQueries)):

            // does this lookup contain a queryset
            // this means its not a duplication but a different request just containing the same name
            if ($lookup->queryset):
                throw new ValueError(sprintf("'%s' lookup was already seen with a different queryset. ".
                    'You may need to adjust the ordering of your lookups.'.$lookup->prefetchTo));
            endif;

            // just pass this is just a duplication
            continue;
        endif;

        $objList = $instances;

        $throughtAttrs = StringHelper::split(BaseLookup::$lookupPattern, $lookup->prefetchThrough);
        foreach ($throughtAttrs as $level => $throughtAttr) :
            if (count($objList) == 0):
                break;
            endif;

            $prefetchTo = $lookup->getCurrentPrefetchTo($level);

            if (array_key_exists($prefetchTo, $doneQueries)):
                $objList = ArrayHelper::getValue($doneQueries, $prefetchTo);
                continue; //if its already fetched skip it
            endif;
        endforeach;
    endwhile;

}

/**
 * Enusures all prefetch looks are of the same form i.e. instance of Prefetch.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @param $lookups
 * @param null $prefix
 *
 * @return Prefetch[]
 */
function normalizePrefetchLookup($lookups, $prefix = null)
{

    $results = [];
    foreach ($lookups as $lookup) :
        if (!$lookup instanceof Prefetch):
            $lookup = new Prefetch($lookup);
        endif;
        if ($prefix):
            $lookup->addPrefix($prefix);
        endif;
        $results[] = $lookup;
    endforeach;

    return $results;
}
