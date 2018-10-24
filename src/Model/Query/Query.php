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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\CloneInterface;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Exception\QueryException;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\Node;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Lookup\LookupInterface;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Compiler\SqlFetchBaseCompiler;
use Eddmash\PowerOrm\Model\Query\Expression\BaseExpression;
use Eddmash\PowerOrm\Model\Query\Expression\Col;
use Eddmash\PowerOrm\Model\Query\Expression\ExpResolverInterface;
use Eddmash\PowerOrm\Model\Query\Expression\ResolvableExpInterface;
use Eddmash\PowerOrm\Model\Query\Joinable\BaseJoin;
use Eddmash\PowerOrm\Model\Query\Joinable\BaseTable;
use Eddmash\PowerOrm\Model\Query\Joinable\Join;
use Eddmash\PowerOrm\Model\Query\Joinable\WhereNode;
use function Eddmash\PowerOrm\Model\Query\Expression\count_;
use function Eddmash\PowerOrm\Model\Query\Expression\ref_;
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;
use const Eddmash\PowerOrm\Model\Query\Expression\ORDER_PATTERN;
use Traversable;

const INNER = 'INNER JOIN';
const LOUTER = 'LEFT OUTER JOIN';
const ORDER_DIRECTION = [
    'ASC' => ['ASC', 'DESC'],
    'DESC' => ['DESC', 'ASC'],
];

class Query extends BaseObject implements ExpResolverInterface, CloneInterface, \IteratorAggregate
{
    public $offset;

    public $limit;

    /** @var WhereNode */
    public $where;

    public $tablesAliasList = [];

    /**
     * @var
     */
    public $tableAlias = [];

    /**
     * A assoc array of tables and there BaseJoin instance.
     *
     * @var BaseJoin[]
     */
    public $tableJoinsMap = [];

    public $selectRelected = [];

    public $aliasRefCount = [];

    /**
     * @var Col[]
     */
    public $select = [];

    public $valueSelect = [];

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
     * @var BaseExpression[]
     */
    public $annotations = [];

    public $distict;

    public $distictFields = [];

    public $orderBy = [];

    public $defaultOrdering = true;

    // Arbitrary limit for select_related to prevents infinite recursion.
    public $maxDepth = 5;

    public $columnInfoCache;

    public $usedTableAlias = [];

    /**
     * if null no grouping is done
     * if true group by select fields
     * if array fields in array are used to group.
     *
     * @var null
     */
    public $groupBy = null;

    /**
     * @var bool Dictates if the order by is done in the asc or desc manner,
     *           true indicates the asc manner
     */
    public $standardOrdering = true;

    /**
     * @var string
     */
    private $whereClass;

    /**
     * Query constructor.
     *
     * @param Model     $model
     * @param WhereNode $whereClass
     *
     * @internal param string $where
     */
    public function __construct(Model $model, $whereClass = WhereNode::class)
    {
        $this->selectRelected = false;
        $this->model = $model;
        $this->where = $whereClass::createObject();
        $this->whereClass = $whereClass;
    }

    public static function createObject(Model $model)
    {
        return new self($model);
    }

    /**
     * Determine how a field needs to be ordered.
     *
     * @param        $orderName
     * @param string $defaultOrder
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getOrderDirection($orderName, $defaultOrder = 'ASC')
    {
        $order = ORDER_DIRECTION[$defaultOrder];

        if (StringHelper::startsWith($orderName, '-')) {
            return [str_replace('-', '', $orderName), $order[1]];
        }

        return [$orderName, $order[0]];
    }

    /**
     * manually Adds field to be used on the select statement.
     *
     * @param Col $col
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addSelect(Col $col)
    {
        $this->useDefaultCols = false;
        $this->select[] = $col;
    }

    public function addFields($fieldNames, $allowM2M = true)
    {
        $alias = $this->getInitialAlias();
        $meta = $this->getMeta();

        foreach ($fieldNames as $fieldName) {
            $names = StringHelper::split(BaseLookup::$lookupPattern, $fieldName);
            list($field, $targets, $joinList, $paths) = $this->setupJoins(
                $names,
                $meta,
                $alias
            );

            /** @var $targets Field[] */
            list($targets, $finalAlias, $joinList) = $this->trimJoins(
                $targets,
                $joinList,
                $paths
            );

            foreach ($targets as $target) {
                $this->addSelect($target->getColExpression($finalAlias));
            }
        }
    }

    public function clearSelectedFields()
    {
        $this->select = [];
        $this->valueSelect = [];
    }

    /**
     * @return WhereNode
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param $lookup
     * @param $rhs
     * @param $lhs Col
     *
     * @return LookupInterface
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildCondition($lookup, $lhs, $rhs)
    {
        $lookup = (array) $lookup;
        $lookup = $lhs->getLookup($lookup[0]);
        /* @var $lookup LookupInterface */
        return $lookup::createObject($lhs, $rhs);
    }

    /**
     * Builds a WhereNode for a single filter clause, but doesn't add it to
     * this Query. Query->add_q() will then add
     * this filter to the where Node.
     *
     * @param $condition
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function buildFilter(
        $conditions,
        $connector = AND_CONNECTOR,
        $allowJoins = true,
        &$canReuse = null
    ) {
        reset($conditions);
        $name = key($conditions);
        $value = current($conditions);

        list($lookups, $fieldParts) = $this->solveLookupType($name);
        list($value, $lookups, $usedJoins) = $this->prepareLookupValue(
            $value,
            $lookups,
            $canReuse,
            $allowJoins
        );

        $whereClass = ($this->whereClass);
        $clause = $whereClass::createObject();
        $meta = $this->getMeta();
        $alias = $this->getInitialAlias();

        list($field, $targets, $joinList, $paths) = $this->setupJoins(
            $fieldParts,
            $meta,
            $alias
        );

        if (!is_null($canReuse)) {
            foreach ($joinList as $list) {
                $canReuse[] = $list;
            }
        }

        $usedJoins = array_merge(
            array_unique($usedJoins),
            array_unique($joinList)
        );
        /* @var $targets Field[] */
        /* @var $field Field */
        list($targets, $alias, $joinList) = $this->trimJoins(
            $targets,
            $joinList,
            $paths
        );

        if ($field->isRelation) {
            $lookupClass = $field->getLookup($lookups[0]);
            if (1 == count($targets)) {
                $col = $targets[0]->getColExpression($alias, $field);
                $condition = $lookupClass::createObject($col, $value);
            } else {
                throw new QueryException(sprintf('Got %s targets columns', count($targets)));
            }
        } else {
            $col = $targets[0]->getColExpression($alias, $field);
            $condition = $this->buildCondition($lookups, $col, $value);
        }

        $clause->add($condition, AND_CONNECTOR);

        //todo joins
        return [$clause, $usedJoins];
    }

    /**
     * @param array $valueSelect
     */
    public function setValueSelect($valueSelect)
    {
        $this->selectRelected = false;
        $this->clearSelectedFields();

        if ($this->groupBy) {
            //todo
        }

        if ($valueSelect) {
            $this->useDefaultCols = false;
        } else {
            $valueSelect = [];
            foreach ($this->model->getMeta()->getConcreteFields() as $field) {
                $valueSelect[] = $field->getName();
            }
            //todo annotations
        }

        $this->valueSelect = $valueSelect;
        $this->addFields($valueSelect, true);
    }

    // where (
    //      blog_text='men are' and (
    //                              headline='matt' or
    //                              name = 'adsd' or
    //                              not (pub_date=2012)
    //                              )
    // )
    //$users = \App\Models\Entry::objects()->filter([
    //    "blog_text" => "men are",
    //    or_([
    //        'headline' => 'matt',
    //        "n_comments" => "adsd"
    //    ])
    //]);
    public function addQ(Q $q)
    {
        $aliases = [];
        foreach ($this->tableJoinsMap as $key => $join) {
            if (INNER === $join->getJoinType()) {
                $aliases[] = $key;
            }
        }

        $clause = $this->_addQ($q, $this->usedTableAlias)[0];

        if ($clause) {
            $this->where->add($clause, AND_CONNECTOR);
        }

        $this->changeToInnerjoin($aliases);
    }

    private function _addQ(
        Q $q,
        &$usedAliases,
        $allowJoins = true,
        $currentNegated = false
    ) {
        $connector = $q->getConnector();

        // current is true only if one and only is true.
        $currentNegated = $currentNegated ^ $q->isNegated();

        $whereClass = $this->whereClass;
        $targetClause = $whereClass::createObject(
            null,
            $connector,
            $q->isNegated()
        );

        $joinpromoter = new JoinPromoter(
            $connector,
            count($q->getChildren()),
            $currentNegated
        );
        foreach ($q->getChildren() as $child) {
            if ($child instanceof Node) {
                list($childClause, $neededInner) = $this->_addQ(
                    $child,
                    $usedAliases,
                    $allowJoins
                );
            } else {
                list($childClause, $neededInner) = $this->buildFilter(
                    $child,
                    $connector,
                    $allowJoins,
                    $usedAliases
                );
            }

            if ($childClause) {
                $targetClause->add($childClause, $connector);
            }

            $joinpromoter->addVotes($neededInner);
        }

        $neededInner = $joinpromoter->updateJoinType($this);

        return [$targetClause, $neededInner];
    }

    public function setGroupBy()
    {
        $this->groupBy = [];

        foreach ($this->select as $col) {
            $this->groupBy[] = $col;
        }

        if ($this->annotations) {
            foreach ($this->annotations as $alias => $annotation) {
                foreach ($annotation->getGroupByCols() as $groupByCol) {
                    $this->groupBy[] = $groupByCol;
                }
            }
        }
    }

    /**
     * Adds items from the 'ordering' sequence to the query's "order by" clause.
     * These items are either
     * field names (not column names) -- possibly with a direction
     * prefix ('-' or '?') -- or OrderBy
     * expressions.
     *
     * If 'ordering' is empty, all ordering is cleared from the query.
     *
     * @param $fieldNames
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addOrdering($fieldNames = [])
    {
        $errors = [];

        foreach ($fieldNames as $fieldName) {
            if (!$fieldName instanceof ResolvableExpInterface &&
                !preg_match(ORDER_PATTERN, $fieldName)
            ) {
                $errors[] = $fieldName;
            }

            if (is_object($fieldName) &&
                property_exists($fieldName, 'containsAggregate')) {
                throw new FieldError(
                    sprintf(
                        'Using an aggregate in orderBy() without also '.
                        'including '.
                        'it in annotate() is not allowed: %s',
                        $fieldName
                    )
                );
            }
        }

        if ($errors) {
            throw new FieldError(
                sprintf(
                    'Invalid orderBy arguments: %s',
                    json_encode($errors)
                )
            );
        }

        if ($fieldNames) {
            $this->orderBy = array_merge($this->orderBy, $fieldNames);
        } else {
            $this->defaultOrdering = false;
        }
    }

    public function getMeta()
    {
        return $this->model->getMeta();
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return SqlFetchBaseCompiler
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getSqlCompiler(ConnectionInterface $connection)
    {
        $compiler = $this->getCompilerClass();

        return new $compiler($this, $connection);
    }

    /**
     * Return the class to use when compiling this query into an sql string.
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function getCompilerClass()
    {
        return SqlFetchBaseCompiler::class;
    }

    private function solveLookupType($name)
    {
        // get lookupand field
        $split_names = StringHelper::split(BaseLookup::$lookupPattern, $name);

        $paths = $this->getNamesPath($split_names, $this->getMeta());

        $lookup = $paths['others'];

        $fieldParts = [];
        foreach ($split_names as $name) {
            if (in_array($name, $lookup)) {
                continue;
            }
            $fieldParts[] = $name;
        }

        if (0 === count($lookup)) {
            $lookup[] = 'exact';
        } elseif (count($fieldParts) > 1) {
            if (!$fieldParts) {
                throw new FieldError(
                    sprintf(
                        'Invalid lookup "%s" for model %s".',
                        $name,
                        $this->getMeta()->getNSModelName()
                    )
                );
            }
        }

        return [$lookup, $fieldParts];
    }

    /**
     * @param $value
     * @param $lookups
     * @param $canReuse
     * @param bool $allowJoins
     *
     * @return array
     *
     * @throws ValueError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    private function prepareLookupValue($value, $lookups, &$canReuse, $allowJoins = true)
    {
        $usedJoins = [];
        if (empty($lookups)) {
            $lookups = ['exact'];
        }

        // Interpret '__exact=null' as the sql 'is NULL'; otherwise, reject all
        // uses of null as a query value.
        if (is_null($value)) {
            if (!in_array(array_pop($lookups), ['exact'])) {
                throw new ValueError('Cannot use "null" as a query value');
            }

            return [true, ['isnull']];
        } elseif ($value instanceof ResolvableExpInterface) {
            $preJoins = $this->aliasRefCount;
            $value = $value->resolveExpression($this, $allowJoins, $canReuse);
            foreach ($this->aliasRefCount as $key => $count) {
                if ($count > ArrayHelper::getValue($preJoins, $key, 0)) {
                    $usedJoins[] = $key;
                }
            }
        }

        if (method_exists($value, '_prepareAsFilterValue')) {
            $value = $value->_prepareAsFilterValue();
        }

        if (is_object($value) && property_exists($value, 'query') &&
            method_exists($value->query, 'bumpPrefix')) {
            /** @var $value Queryset */
            $value = $value->_clone();
            $value->query->bumpPrefix($this);
        }
        // Subqueries need to use a different set of aliases than the
        // outer query. Call bumpPrefix to change aliases of the inner
        // query (the value).
        if (is_object($value) && method_exists($value, 'bumpPrefix')) {
            /** @var $value Query */
            $value = $value->deepClone();
            $value->bumpPrefix($this);
        }

        //todo if value is array
        return [$value, $lookups, $usedJoins];
    }

    /**
     * Readies this instance for use in filter.
     *
     * @return Query
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _prepareAsFilterValue()
    {
        return $this->deepClone();
    }

    public function _prepare()
    {
        return $this;
    }

    /**
     * Resolve the paths to follow when making queries
     * The names is an array of fields that need to be followed for a specific condition e.g. ['order__product'=>...].
     *
     * The order__product is split into ['order', 'product'] which is what is passed to this method.
     *
     * @param      $names         array of fields to follow
     * @param Meta $meta
     * @param bool $failOnMissing
     *
     * @return array
     *
     * @throws FieldError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function getNamesPath($names, Meta $meta, $failOnMissing = false)
    {
        $paths = $targets = [];
        $finalField = null;

        $posReached = 1;

        /* @var $field Field|RelatedField|ForeignObjectRel */
        foreach ($names as $pos => $name) {
            $posReached += $pos;

            if (PRIMARY_KEY_ID === $name) {
                $name = $meta->primaryKey->getName();
            }

            $field = null;

            try {
                $field = $meta->getField($name);
            } catch (FieldDoesNotExist $e) {
                //todo check in annotations to
                $available = Tools::getFieldNamesFromMeta($meta);
                if ($failOnMissing) {
                    throw new FieldError(
                        sprintf(
                            "Cannot resolve keyword '%s.%s' ".
                            'into field. Choices are: [ %s ]',
                            $meta->getNSModelName(),
                            $name,
                            implode(', ', $available)
                        )
                    );
                } else {
                    //reduce the position reached by one
                    --$posReached;
                    break;
                }
            }

            // todo Check if we need any joins for concrete inheritance cases (the
            // field lives in parent, but we are currently in one of its
            // children)
            if ($field->hasMethod('getPathInfo')) {
                $pathsInfos = $field->getPathInfo();
                reset($pathsInfos);
                $last = end($pathsInfos);

                $meta = ArrayHelper::getValue($last, 'toMeta');
                $finalField = ArrayHelper::getValue($last, 'joinField');
                $targets = ArrayHelper::getValue($last, 'targetFields');
                $paths = array_merge($paths, $pathsInfos);
            } else {
                // none relational field
                $finalField = null;
                $finalField = $field;

                $targets = [$field];
                // no need to go on since this is a none relation field.
                break;
            }
        }

        return [
            'paths' => $paths,
            'finalField' => $finalField,
            'targets' => $targets,
            'others' => array_slice($names, $posReached),
        ];
    }

    public function setLimit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function getInitialAlias()
    {
        if ($this->tablesAliasList) {
            // get the first one
            $alias = $this->tablesAliasList[0];
        } else {
            $alias = $this->join(
                new BaseTable(
                    $this->getMeta()->getDbTable(),
                    null
                )
            );
        }

        return $alias;
    }

    /**
     * @param      $names
     * @param Meta $meta
     * @param      $alias
     *
     * @return array
     *
     * @throws FieldError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function setupJoins($names, Meta $meta, $alias)
    {
        if (is_null($alias)) {
            $alias = $this->getInitialAlias();
        }
        $joins = [$alias];

        $namesPaths = $this->getNamesPath($names, $meta, true);

        $pathInfos = $namesPaths['paths'];

        /* @var $meta Meta */
        foreach ($pathInfos as $pathInfo) {
            $meta = $pathInfo['toMeta'];

            if ($pathInfo['direct']) {
                $nullable = $this->isNullable($pathInfo['joinField']);
            } else {
                $nullable = true;
            }
            $join = new Join();
            $join->setTableName($meta->getDbTable());
            $join->setParentAlias($alias);
            $join->setJoinType(INNER);
            $join->setJoinField($pathInfo['joinField']);
            $join->setNullable($nullable);

            $alias = $this->join($join);

            $joins[] = $alias;
        }

        return [
            $namesPaths['finalField'],
            $namesPaths['targets'],
            $joins,
            $pathInfos,
            $meta,
        ];
    }

    public function join(BaseJoin $join, $reuse = null)
    {
        // check if we can resuse an alias
        $resuableAliases = [];
        foreach ($this->tableJoinsMap as $key => $item) {
            if ((null == $reuse || ArrayHelper::hasKey($reuse, $key)) &&
                $join->equal($item)) {
                $resuableAliases[] = $key;
            }
        }

        if ($resuableAliases) {
            ++$this->aliasRefCount[$resuableAliases[0]];

            return $resuableAliases[0];
        }

        list($alias) = $this->getTableAlias($join->getTableName(), false);

        if ($join->getJoinType()) {
            if (LOUTER === $this->tableJoinsMap[$join->getParentAlias()]->getJoinType() ||
                $join->getNullable()) {
                $joinType = LOUTER;
            } else {
                $joinType = INNER;
            }
            $join->setJoinType($joinType);
        }

        $join->setTableAlias($alias);
        $this->tableJoinsMap[$alias] = $join;
        $this->tablesAliasList[] = $alias;

        return $alias;
    }

    /**
     * Change join type from LOUTER to INNER for all joins in aliases.
     *
     * Similarly to changeToOuterJoin(), this method must ensure no join chains
     * containing first an outer, then an inner
     * join are generated.
     * If we are demoting {A->C} join in chain {A LOUTER B LOUTER C} then we
     * must demote {A->B} automatically, or
     * otherwise the demotion of {A->B} doesn't actually change anything in the
     * query results. .
     *
     * @param array $aliases
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function changeToInnerjoin($aliases = [])
    {
        /* @var $join Join */
        /* @var $parent Join */
        while ($aliases) {
            $alias = array_pop($aliases);
            $join = $this->tableJoinsMap[$alias];
            if (LOUTER == $join->getJoinType()) {
                $this->tableJoinsMap[$alias] = $join->demote();
                $parent = $this->tableJoinsMap[$join->getParentAlias()];
                if (INNER == $parent->getJoinType()) {
                    $aliases[] = $join->getParentAlias();
                }
            }
        }
    }

    /**
     * Promotes recursively the join type of given aliases and its children to
     * an outer join. If 'unconditional' is False, the join is only promoted if
     * it is nullable or the parent join is an outer join.
     *
     * The children promotion is done to avoid join chains that contain a LOUTER b INNER c. So, if we have currently
     * a INNER b INNER c and a->b is promoted, then we must also promote b->c automatically, or otherwise the promotion
     * of a->b doesn't actually change anything in the query results.
     *
     * @param $aliases
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function changeToOuterJoin($aliases)
    {
        /* @var $join Join */
        /* @var $parent Join */
        while ($aliases) {
            $alias = array_pop($aliases);
            $join = $this->tableJoinsMap[$alias];

            // for the first join this should be true because its not a join
            // but a basetable that will be used in the from part of the query
            if (null == $join->getJoinType()) {
                continue;
            }

            // only the first alias is allowed to havea null join type
            assert(null !== $join->getJoinType());

            $parentAlias = $join->getParentAlias();
            $parentIsOuter = ($parentAlias && LOUTER == $this->tableJoinsMap[$parentAlias]->getJoinType());
            $aliasIsOuter = (LOUTER == $join->getJoinType());

            if (($join->getNullable() || $parentIsOuter) && !$aliasIsOuter) {
                $this->tableJoinsMap[$alias] = $join->promote();
                // since we have just change the join type of alias we need to update
                // any thing else that refers to it
                foreach ($this->tableJoinsMap as $key => $join) {
                    if ($join->getParentAlias() == $alias &&
                        !ArrayHelper::hasKey($aliases, $key)
                    ) {
                        $aliases[] = $key;
                    }
                }
            }
        }
    }

    /**
     * @param $targets
     * @param $joinList
     * @param $path
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function trimJoins($targets, $joinList, $path)
    {
        /* @var $joinField RelatedField */
        /* @var $field Field */
        /* @var $relField Field[] */

        foreach (array_reverse($path) as $info) {
            if (!$info['direct'] || 1 === count($joinList)) {
                break;
            }

            $joinTargets = [];
            $currentTargets = [];
            $joinField = $info['joinField'];

            foreach ($joinField->getForeignRelatedFields() as $field) {
                $joinTargets[] = $field->getColumnName();
            }

            foreach ($targets as $field) {
                $currentTargets[] = $field->getColumnName();
            }

            if (!array_intersect($joinTargets, $currentTargets)) {
                break;
            }

            $relFields = [$joinField->getRelatedFields()];
            $relMap = [];
            foreach ($relFields as $relField) {
                if (in_array($relField[1]->getColumnName(), $currentTargets)) {
                    $relMap[$relField[1]->getColumnName()] = $relField[0];
                }
            }

            $targetsNew = [];
            foreach ($targets as $target) {
                $targetsNew[] = $relMap[$target->getColumnName()];
            }
            $targets = $targetsNew;

            $this->unrefAlias(array_pop($joinList));
        }

        $alias = array_slice($joinList, -1)[0];

        return [$targets, $alias, $joinList];
    }

    private function unrefAlias($alias, $amount = 1)
    {
        $this->aliasRefCount[$alias] -= $amount;
    }

    public function getTableAlias($tableName, $create = false)
    {
        // we use reference since we need to do an update
        $aliases = &$this->tableAlias[$tableName];

        if ($aliases && false === $create) {
            $alias = $aliases[0];
            ++$this->aliasRefCount[$alias];

            return [$alias, false];
        }

        // we create a new alias
        if ($aliases) {
            $aliases[] = sprintf('%s%s', $tableName, count($this->tableJoinsMap));
        } else {
            $this->tableAlias[$tableName] = [$tableName];
        }

        $alias = $tableName;
        $this->aliasRefCount[$alias] = 1;

        return [$alias, true];
    }

    public function addAnnotation($kwargs = [])
    {
        /** @var $annotation BaseExpression */
        $annotation = ArrayHelper::getValue($kwargs, 'annotation');
        $alias = ArrayHelper::getValue($kwargs, 'alias');
        // indicate this field annotation produces summarise out over serveral
        // records, i.e. all aggregate functions e.g. avg(), count() etc
        $isSummary = ArrayHelper::getValue($kwargs, 'isSummary', false);

        // we resolve what the annotation points to,
        // is it a Col that indicates that its using a field on the model
        // as a source
        // a Ref which might indicate its using an annotation as its source

        $annotation = $annotation->resolveExpression(
            $this,
            true,
            null,
            $isSummary
        );
        $this->annotations[$alias] = $annotation;
    }

    /**
     * Sets up the selectRelated data structure so that we only select certain
     * related models
     * (as opposed to all models, when $this->selectRelated=true).
     *
     * @param array $fields
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addSelectRelected(array $fields)
    {
        if (is_bool($this->selectRelected)) {
            $relatedFields = [];
        } else {
            $relatedFields = $this->selectRelected;
        }

        // we need the path to follow
        //
        //array(
        //  "order" => array(
        //    "buyer" => array()
        //  )
        //  "product" => array()
        //)
        foreach ($fields as $field) {
            $names = StringHelper::split(BaseLookup::$lookupPattern, $field);
            // we use by reference so that we assigned the values back to the original array
            $d = &$relatedFields;
            foreach ($names as $name) {
                $d = &$d[$name];
                if (empty($d)) {
                    $d = [];
                }
            }
        }
        $this->selectRelected = $relatedFields;
    }

    /**
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getAggregation(
        ConnectionInterface $connection,
        $addedAggregateNames = []
    ) {
        if (!$this->annotations) {
            return [];
        }
        $hasLimit = ($this->offset || $this->limit);
        $hasExistingAnnotations = false;
        foreach ($this->annotations as $alias => $annotation) {
            $hasExistingAnnotations = ($hasExistingAnnotations ||
                !in_array($alias, $addedAggregateNames));
        }

        // we have of $hasExistingAnnotations  we need to make the core query a
        // subquery and aggregate over it.
        if ($hasExistingAnnotations || $hasLimit || $this->distict ||
            is_array($this->groupBy)) {
            $outQuery = new AggregateQuery($this->model);
            $innerQuery = $this->deepClone();

            $innerQuery->selectRelected = false;

            if (!$hasLimit && !$this->distictFields) {
                // Queries with distinct_fields need ordering and when a limit
                // is applied we must take the slice from the ordered query.
                // Otherwise no need for ordering, so clear.
                $innerQuery->clearOrdering(true);
            }

            if (!$innerQuery->distict) {
                // if we are using default columns and we already have
                // aggregate annotations existing
                // then we must make sure the inner
                // query is grouped by the main model's primary key. However,
                // clearing the select clause can alter results if distinct is
                // used.
                if ($innerQuery->useDefaultCols && $hasExistingAnnotations) {
                    $innerQuery->groupBy = [
                        $this->getMeta()->primaryKey
                            ->getColExpression($innerQuery->getInitialAlias()),
                    ];
                }
                $innerQuery->useDefaultCols = false;
            }

            // add annotations to the outerquery todo
            foreach ($innerQuery->annotations as $alias => $annotation) {
                if ($annotation->isSummarize()) {
                    $outQuery->annotations[$alias] = $annotation;
                    unset($innerQuery->annotations[$alias]);
                }
            }

            if ($innerQuery->select == [] && !$innerQuery->useDefaultCols) {
                $innerQuery->select = [
                    $this->getMeta()->primaryKey->getColExpression(
                        $innerQuery->getInitialAlias()
                    ),
                ];
            }

            $outQuery->addSubQuery($innerQuery, $connection);
        } else {
            $outQuery = $this;
            $outQuery->select = [];
            $outQuery->useDefaultCols = false;
        }
        $outQuery->clearOrdering(true);
        $outQuery->clearLimits();
        $outQuery->selectRelected = false;

        $results = $outQuery->getSqlCompiler($connection)
            ->executeSql()->fetch();

        $result = [];
        foreach (array_combine(
                     array_keys($outQuery->annotations),
                     array_values($results)
                 ) as $key => $item) {
            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * Removes any ordering settings.
     *
     * @param bool $forceEmpty If True, there will be no ordering in the resulting query (not even the model's
     *                         default)
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function clearOrdering($forceEmpty = false)
    {
        $this->orderBy = [];
        if ($forceEmpty) {
            $this->defaultOrdering = false;
        }
    }

    /**
     * Clears any existing limits.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function clearLimits()
    {
        $this->limit = $this->offset = null;
    }

    /**
     * Returns True if adding filters to this instance is still possible.
     *
     * Typically, this means no limits or offsets have been put on the results.
     *
     * @return bool
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function isFilterable(): bool
    {
        return empty($this->offset) && empty($this->limit);
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return mixed
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getCount(ConnectionInterface $connection)
    {
        $obj = $this->deepClone();
        $obj->addAnnotation(['annotation' => count_('*'), 'alias' => '_count', 'isSummary' => true]);
        $alias = '_count';
        $result = $obj->getAggregation($connection, [$alias]);

        return ArrayHelper::getValue($result, $alias, 0);
    }

    /**
     * @param string $class
     *
     * @return $this
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deepClone($class = null)
    {
        $class = (is_null($class)) ? static::class : $class;
        /** @var $obj Query */
        $obj = new $class($this->model);
        $obj->aliasRefCount = $this->aliasRefCount;
        $obj->useDefaultCols = $this->useDefaultCols;
        $obj->tableAlias = $this->tableAlias;
        $obj->tableJoinsMap = $this->tableJoinsMap;
        $obj->tablesAliasList = $this->tablesAliasList;
        $obj->select = $this->select;
        $obj->groupBy = $this->groupBy;
        $obj->valueSelect = $this->valueSelect;
        $obj->selectRelected = $this->selectRelected;
        $obj->standardOrdering = $this->standardOrdering;
        $obj->annotations = $this->annotations;
        $obj->defaultOrdering = $this->defaultOrdering;
        $obj->orderBy = $this->orderBy;
        $obj->offset = $this->offset;
        $obj->limit = $this->limit;
        $obj->where = $this->where->deepClone();

        return $obj;
    }

    public function getResultsIterator(ConnectionInterface $connection)
    {
        $preparedResults = [];

        // since php pdo normally returns an assoc array, we ask it return the values in form an array indexed
        // by column number as returned in the corresponding result set, starting at column 0.
        // this to avoid issues where joins result in columns with the same name e.g. user.id joined by blog.id
        $results = $this->execute($connection)->fetchAll(\PDO::FETCH_NUM);
        foreach ($results as $row) {
            $preparedResults[] = $this->preparedResults($connection, $row);
        }

        return $preparedResults;
    }

    /**
     * Gets all names for the fields in the query model, inclusing reverse fields.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFieldChoices()
    {
        $fields = [];
        foreach ($this->getMeta()->getFields() as $field) {
            if (!$field->isRelation) {
                continue;
            }
            $fields[] = $field->getName();
        }

        foreach ($this->getMeta()->getReverseRelatedObjects() as $reverseRelatedObject) {
            if ($reverseRelatedObject->relation->fromField->isUnique()) {
                $fields[] = $reverseRelatedObject->relation->fromField->getRelatedQueryName();
            }
        }

        return $fields;
    }

    /**
     * We check if a field is nullable.
     *
     * @param Field $joinField
     *
     * @since  1.1.0
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
     * @param ConnectionInterface $connection
     * @param                     $values
     *
     * @return array
     *
     * @throws FieldError
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preparedResults(ConnectionInterface $connection, $values)
    {
        $preparedValues = [];

        /* @var $col Col */
        /* @var $field Field */
        foreach ($this->select as $pos => $selectColumn) {
            $col = $selectColumn[0];
            $field = $col->getOutputField();
            $val = ArrayHelper::getValue($values, $pos);
            // first use the inbuilt converters
            try {
                $val = Type::getType(
                    $field->dbType($connection)
                )->convertToPHPValue($val, $connection->getDatabasePlatform());
            } catch (DBALException $exception) {
            }

            // use the field converters if any were provided by the user.
            $converters = $field->getDbConverters($connection);

            if ($converters) {
                foreach ($converters as $converter) {
                    $val = call_user_func($converter, $connection, $val, $field);
                }
            }
            $preparedValues[] = $val;
        }

        return $preparedValues;
    }

    /**@inheritdoc */
    public function resolveExpression(
        $name,
        $allowJoins = true,
        &$reuse = null,
        $summarize = false
    ) {
        if (!$allowJoins &&
            StringHelper::contains($name, BaseLookup::LOOKUP_SEPARATOR)
        ) {
            throw new FieldError(
                'Joined field references are not permitted in this query');
        }

        if (ArrayHelper::hasKey($this->annotations, $name)) {
            // Summarize currently means we are doing an aggregate() query
            // which is executed as a wrapped subquery if any of the
            // aggregate() elements reference an existing annotation. In
            // that case we need to return a Ref to the subquery's annotation.
            if ($summarize) {
                return ref_($name, $this->annotations[$name]);
            } else {
                return ArrayHelper::getValue($this->annotations, $name);
            }
        } else {
            $splitNames = StringHelper::split(BaseLookup::$lookupPattern, $name);

            list($field, $orgTargets, $joinList, $paths) = $this->setupJoins(
                $splitNames,
                $this->getMeta(),
                $this->getInitialAlias()
            );

            /* @var $targets Field[] */
            /* @var $field Field */

            list($targets, $finalAlias, $joinList) = $this->trimJoins(
                $orgTargets,
                $joinList,
                $paths
            );
            if (count($targets) > 1) {
                throw new FieldError(
                    'Referencing multicolumn fields'.
                    " with F() objects isn't supported"
                );
            }

            if (!is_null($reuse)) {
                foreach ($joinList as $item) {
                    $reuse[] = $item;
                }
            }

            $col = $targets[0]->getColExpression(
                end($joinList),
                $orgTargets[0]
            );

            return $col;
        }
    }

    public function toSubQuery(ConnectionInterface $connection)
    {
        $this->isSubQuery = true;

        //todo clear ordering
        return $this;
    }

    public function hasResults(ConnectionInterface $connection)
    {
        $query = $this->deepClone();
        //todo handle distinct and group by
        $query->clearOrdering(true);
        $query->setLimit(null, 1);
        $compiler = $query->getSqlCompiler($connection);

        return $compiler->hasResults();
    }

    /**
     * Makes a the query return the sql to be executed.
     *
     * @return string
     */
    public function _getSql()
    {
        $sql = '';
        $params = [];
        try {
            list($sql, $params) = $this->getSqlCompiler(BaseOrm::getDbConnection())->asSql();
        } catch (OrmException $e) {
        }

        $sql = str_replace('?', '%s', $sql);

        return vsprintf($sql, $params);
    }

    /**
     * Retrieve an external iterator.
     *
     * @see http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *                     <b>Traversable</b>
     *
     * @since 5.0.0
     */
    public function getIterator()
    {
        throw new OrmException('Query is not iterable');
    }

    public function bumpPrefix(Query $outerQuery)
    {
    }
}

/**
 * @param Model[]        $instances
 * @param Prefetch|array $lookups
 *
 * @throws ValueError
 * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
 * @throws \Eddmash\PowerOrm\Exception\KeyError
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function prefetchRelatedObjects($instances, $lookups)
{
    if (!$lookups instanceof Prefetch) {
        $msg = sprintf("method '%s()' expects parameter 'lookup' to be an array", __FUNCTION__);
        Tools::ensureParamIsArray($lookups, $msg);
    }

    if (0 == count($instances)) {
        return;
    }

    //We need to be able to dynamically add to the list of prefetch_related
    //lookups that we look up (see below).  So we need some book keeping to
    //ensure we don't do duplicate work.
    $doneQueries = [];  // assoc_array of things like 'foo__bar': [results]
    $lookups = normalizePrefetchLookup($lookups);

    /* @var $lookup Prefetch */
    while ($lookups) {
        $lookup = array_shift($lookups);

        // have already worked on a lookup that has similar name
        if (array_key_exists($lookup->prefetchTo, $doneQueries)) {
            // does this lookup contain a queryset
            // this means its not a duplication but a different request just containing the same name
            if ($lookup->queryset) {
                throw new ValueError(
                    sprintf(
                        "'%s' lookup was already seen with a different queryset. ".
                        'You may need to adjust the ordering of your lookups.',
                        $lookup->prefetchTo
                    )
                );
            }

            // just pass this is just a duplication
            continue;
        }

        $objList = $instances;

        $throughtAttrs = StringHelper::split(BaseLookup::$lookupPattern, $lookup->prefetchThrough);
        foreach ($throughtAttrs as $level => $throughtAttr) {
            if (0 == count($objList)) {
                break;
            }

            $prefetchTo = $lookup->getCurrentPrefetchTo($level);

            if (array_key_exists($prefetchTo, $doneQueries)) {
                $objList = ArrayHelper::getValue($doneQueries, $prefetchTo);

                continue; //if its already fetched skip it
            }
        }
    }
}

/**
 * Enusures all prefetch looks are of the same form i.e. instance of Prefetch.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 * @param      $lookups
 * @param null $prefix
 *
 * @return Prefetch[]
 */
function normalizePrefetchLookup($lookups, $prefix = null)
{
    $results = [];
    foreach ($lookups as $lookup) {
        if (!$lookup instanceof Prefetch) {
            $lookup = new Prefetch($lookup);
        }
        if ($prefix) {
            $lookup->addPrefix($prefix);
        }
        $results[] = $lookup;
    }

    return $results;
}
