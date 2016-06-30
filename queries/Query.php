<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/29/16
 * Time: 6:36 PM
 */

namespace powerorm\queries;


interface Query
{

    public function one();
    public function all();
    public function exists();
    public function with($conditions);
    public function distinct();
    public function exclude($conditions);
    public function sql();
    public function filter();
    public function limit($size, $start = 0);
    public function group_by($condition);
    public function order_by($criteria);
    public function first();

    public function last();
    public function max($column);
    public function min($column);
    public function delete();
    public function save();
}