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
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Lookup\LookupInterface;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;
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
    private $offset;
    private $limit;

    /**@var Where */
    private $where;
    private $tables = [];
    private $tableMap = [];

    /**
     * @var BaseJoin[]
     */
    private $tableAlias = [];

    //[
    //  'local' => [],
    //  'related' => [],
    //]
    private $select = [
    ];
    private $isSubQuery = false;

    /**
     * @var Model
     */
    private $model;

    /**
     * if true, get the columns to fetch from the model itself.
     *
     * @var
     */
    private $defaultCols = true;

    /**
     * Query constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public static function createObject(Model $model)
    {
        return new self($model);
    }

    public function asSql(Connection $connection)
    {
        $params = [];
        $results = ['SELECT'];
        $results[] = $this->getSelect();

        $results[] = 'FROM';
        list($fromClause, $fromParams) = $this->getFrom($connection);
        $results = array_merge($results, $fromClause);
        $params = array_merge($params, $fromParams);

        if ($this->where):
            $results[] = 'WHERE';
            list($sql, $whereParams) = $this->getWhereSql($connection);
            $results[] = $sql;
            $params = array_merge($params, $whereParams);
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

    public function getWhereSql(Connection $connection)
    {
        return $this->where->asSql($connection);
    }

    public function getNestedSql(Connection $connection)
    {
        $this->setIsSubQuery(true);

        return $this->asSql($connection);
    }

    /**
     * @param array $where
     */
    public function addWhere(Where $where)
    {
        $this->where = $where;
    }

    /**
     * @param array $select
     */
    public function addSelect($select, $flush = false)
    {
        if ($flush):
            $this->select = [];
        endif;
        if (!is_array($select)):
            $select = [$select];
        endif;

        $this->select = array_merge($this->select, $select);
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        if ($this->defaultCols):

            $meta = $this->model->meta;
            /** @var $field Field */
            foreach ($meta->getLocalConcreteFields() as $name => $field) :
                $this->select[] = $field->getColumnName();
            endforeach;
        endif;

        return implode(', ', $this->select);
    }

    public function getFrom(Connection $connection)
    {
        $result = [];
        $params = [];
        foreach ($this->tables as $alias) :
            try {
                /**@var $from BaseJoin */;
                $from = ArrayHelper::getValue($this->tableMap, $alias, ArrayHelper::THROW_ERROR);
                list($fromSql, $fromParams) = $from->asSql($connection);
                array_push($result, $fromSql);
                $params = array_merge($params, $fromParams);
            } catch (KeyError $e) {
                continue;
            }
        endforeach;

        return [$result, $params];
    }

    /**
     * @return array
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
    private function buildFilter($condition, $negate = false)
    {
        $alias = $this->getInitialAlias();
        $where = Where::createObject();
        foreach ($condition as $name => $value) :
            list($connector, $lookups, $fieldParts) = $this->solveLookupType($name);

            list($finalField, $targets, $meta, $join, $paths) = $this->setupJoins(
                $fieldParts,
                $this->model->meta,
                $alias
            );

            $field = $finalField;

            if ($field->isRelation) :
                $lookup = $field->getLookup($lookups[0]);

                $condition = $this->buildCondition($lookups, $targets[0], $value);
            else:

                $condition = $this->buildCondition($lookups, $field, $value);
            endif;

            $where->setConditions($connector, $condition);


        endforeach;

        $this->addWhere($where);
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
        $lookup = $lhs->getLookup($lookup[0]);
        /* @var $lookup LookupInterface */
        $lookup = $lookup::createObject($lhs, $rhs);

        return $lookup;
    }

    private function solveLookupType($name)
    {
        list($connector, $names) = $this->getConnector($name);
        // get lookupand field
        if (preg_match(BaseLookup::$lookupPattern, $names)):
            $split_names = preg_split(BaseLookup::$lookupPattern, $names);
        else:
            $split_names = [$names];
        endif;

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
                        $this->model->meta->modelName
                    )
                );
            endif;
        endif;

        return [$connector, $lookup, $fieldParts];
    }


    public function getNamesPath($names, Meta $meta, $failOnMissing = false)
    {
        $paths = $targets = [];
        $finalField = null;
        $noneField = [];
        foreach ($names as $name) :
            if ($name === PRIMARY_KEY_ID):
                $name = $meta->primaryKey->name;
            endif;

            /**@var $field Field */
            $field = null;

            try {
                $field = $meta->getField($name);
            } catch (FieldDoesNotExist $e) {
                $available = getFieldNamesFromMeta($meta);
                if ($failOnMissing) :

                    throw new FieldError(
                        sprintf(
                            "Cannot resolve keyword '%s' into field. Choices are: [ %s ]",
                            $name,
                            implode(', ', $available)
                        )
                    );
                else:
                    $noneField[] = $name;
                    break;
                endif;
            }

            if ($field->hasMethod('getPathInfo')) :
                $pathsInfos = $field->getPathInfo();
                $pInfo = $pathsInfos[0];
                $finalField = ArrayHelper::getValue($pInfo, 'joinField');
                $targets = ArrayHelper::getValue($pInfo, 'targetFields');
                $paths = array_merge($paths, $pathsInfos);
            else:
                $finalField = $field;
                $targets[] = $field;
            endif;


        endforeach;

        return ["paths" => $paths, "finalField" => $finalField, "targets" => $targets, "others" => $noneField];
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

    private function setupJoins($names, Meta $meta, $alias)
    {
        $joins = [];

        $namesPaths = $this->getNamesPath($names, $meta);
        $pathInfos = $namesPaths['paths'];

        /**@var $meta Meta */
        foreach ($pathInfos as $pathInfo) :
            $meta = $pathInfo['toMeta'];
            $join = new Join();
            $join->setTableName($meta->dbTable);
            $join->setParentAlias($alias);
            $join->setJoinType(INNER);
            $join->setJoinField($pathInfo['joinField']);
            $joinAlias = $this->join($join);

            $joins[] = $joinAlias;
        endforeach;


        return [$namesPaths['finalField'], $namesPaths['targets'], $meta, $joins, $pathInfos];
    }

    /**
     * @return bool
     */
    public function isIsSubQuery()
    {
        return $this->isSubQuery;
    }

    /**
     * @param bool $isSubQuery
     */
    public function setIsSubQuery($isSubQuery)
    {
        $this->isSubQuery = $isSubQuery;
    }

    /**
     * @return mixed
     */
    public function getDefaultCols()
    {
        return $this->defaultCols;
    }

    /**
     * @param mixed $defaultCols
     */
    public function setDefaultCols($defaultCols)
    {
        $this->defaultCols = $defaultCols;
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

    public function getTableAlias($tableName, $create = false)
    {
        if (ArrayHelper::hasKey($this->tableAlias, $tableName) && false === $create):
            return [ArrayHelper::getValue($this->tableAlias, $tableName), false];
        endif;

        $alias = $tableName;
        $this->tableAlias[$alias] = $alias;

        return [$alias, true];
    }
}
