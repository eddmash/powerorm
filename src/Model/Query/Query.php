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
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Lookup\LookupInterface;
use Eddmash\PowerOrm\Model\Model;

class Query extends BaseObject
{
    //[
    //  BaseLookup::AND_CONNECTOR => [],
    //  BaseLookup::OR_CONNECTOR => [],
    //];
    private $where = [];
    private $from = [];

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
        list($fromClause, $fromParams) = $this->getFrom();
        $results = array_merge($results, $fromClause);
        if ($this->where):
            $results[] = 'WHERE';
            list($sql, $whereParams) = $this->getWhereSql($connection);
            $results[] = $sql;
            $params = array_merge($params, $whereParams);
        endif;

        return [implode(' ', $results), $params];
    }

    public function getWhereSql(Connection $connection)
    {
        $whereSql = [];
        $whereParams = [];
        /* @var $lookup BaseLookup */
        foreach ($this->where as $conditions) :

            foreach ($conditions as $connector => $lookup) :
                // if we have another condition already added, add the connector
                if ($whereSql):
                    $whereSql[] = $connector;
                endif;
                list($sql, $parms) = $lookup->asSql($connection);
                $whereSql[] = $sql;
                if(!is_array($parms)):
                    $parms = [$parms];
                endif;
                $whereParams = array_merge($whereParams, $parms);
            endforeach;

        endforeach;

        return [implode(' ', $whereSql), $whereParams];

    }

    public function getNestedSql(Connection $connection)
    {
        $this->setIsSubQuery(true);

        return $this->asSql($connection);
    }

    /**
     * @param array $where
     */
    public function addWhere(LookupInterface $lookup, $connector)
    {
        $this->where[] = [$connector => $lookup];
    }

    /**
     * @param array $select
     */
    public function addSelect($select, $flush = false)
    {
        if ($flush):
            $this->select = [];
        endif;
        if(!is_array($select)):
            $select = [$select];
        endif;

        $this->select = array_merge($this->select, $select);
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        if($this->defaultCols):

            $meta = $this->model->meta;
            /** @var $field Field */
            foreach ($meta->getLocalConcreteFields() as $name => $field) :
                $this->select[] = $field->getColumnName();
            endforeach;
        endif;

        return implode(', ', $this->select);
    }

    public function getFrom()
    {
        return [[$this->model->meta->dbTable], []];

    }

    /**
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
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
}
