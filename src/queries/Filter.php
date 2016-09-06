<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM
 */

namespace eddmash\powerorm\queries;

use eddmash\powerorm\exceptions\OrmExceptions;
use eddmash\powerorm\helpers\Tools;

/**
 * Class Filter
 * @package eddmash\powerorm\queries
 *
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Filter
{
    public $table_name;
    public $conditions;
    public $_database;

    /**
     * Lookup options
     * @internal
     * @var array
     */
    protected $lookup_options = [
        'eq',
        'in',
        'gt',
        'lt',
        'gte',
        'lte',
        'contains',
        'startswith',
        'endswith',
        'isnull',
        'not',
        'notin'];

    public function __construct(&$qbuilder, $table_name = null)
    {
        // pass by reference since we want this where to be associated with the other statements.
        $this->_qbuilder = $qbuilder;
        $this->table_name = $table_name;
    }

    public function validate_lookup($lookup)
    {
        if (!empty($lookup) && !in_array($lookup, $this->lookup_options)):
            throw new OrmExceptions(
                sprintf("`%1\$s` is not a valid lookup parameter the options are %2\$s",
                    $lookup, Tools::stringify($this->lookup_options)));
        endif;
    }

    /**
     * Creates the different types of where clause based on looksup provided in the condition e.g ['name__exact'=>"john"]
     * $this->user->filter(['~name'=>'mather', 'age'=>10])
     * @internal
     * @param string $table_name
     * @param $conditions
     * @throws OrmExceptions
     */
    public function _where($table_name = null, $conditions)
    {
        // default lookup is equal
        $lookup = 'eq';
        $lookup_pattern = "/__/";
        $where_concat_pattern = "/^~[.]*/";

        // we add the or conditions afterwards to avoid them being mistaken for and conditions when they come first
        $or_combine = [];

        // create where clause from the conditions given
        foreach ($conditions as $key => $value) :


            // check which where clause to use
            if (preg_match($lookup_pattern, $key)):
                $options = preg_split($lookup_pattern, $key);
        $key = $options[0];
        $lookup = strtolower($options[1]);
        endif;

            // determine how to combine where statements
            $use_or = preg_match($where_concat_pattern, $key);

            // get the actual key
            if ($use_or):
                $key = preg_split($where_concat_pattern, $key)[1];
        endif;

            // validate lookups
            $this->validate_lookup($lookup);

        $table_name = strtolower($table_name);

            // append table name to key
            if (!empty($table_name)):
                $key = $table_name . ".$key";
        endif;

            // check if we need to use OR to combine
            if ($use_or):
                $or_combine[] = ['lookup' => $lookup, 'key' => $key, 'value' => $value]; else:
                // otherwise use and
                $this->_and_where_concat($lookup, $key, $value);
        endif;

        endforeach;

        // add the or conditions
        foreach ($or_combine as $ors) :
            $this->_or_where_concat($ors['lookup'], $ors['key'], $ors['value']);
        endforeach;
    }

    public function _and_where_concat($lookup, $key, $value)
    {
        switch ($lookup):
            case 'eq':
                $this->_qbuilder->where($key, $value);
        break;
        case 'in':
                $this->_qbuilder->where_in($key, $value);
        break;
        case 'gt':
                $this->_qbuilder->where("$key >", $value);
        break;
        case 'lt':
                $this->_qbuilder->where("$key <", $value);
        break;
        case 'gte':
                $this->_qbuilder->where("$key >=", $value);
        break;
        case 'lte':
                $this->_qbuilder->where("$key <=", $value);
        break;
        case 'contains':
                $this->_qbuilder->like($key, $value, 'both');
        break;
        case 'startswith':
                $this->_qbuilder->like($key, $value, 'after');
        break;
        case 'endswith':
                $this->_qbuilder->like($key, $value, 'before');
        break;
        case 'between':
                if (!is_array($value) || (is_array($value) && count($value) != 2)) {
                    throw new OrmExceptions(
                        sprintf("filter() using between expected value to be an array, with two values only"));
                }
        $this->_qbuilder->where("$key BETWEEN $value[0] AND $value[1] ");
        break;
        case 'isnull':
                //TODO NOTNULL
                if ($value):
                    $value = null;
        endif;
        $this->_qbuilder->where($key, $value);
        break;
        case 'not':
                $this->_qbuilder->where("$key !=", $value);
        break;
        case 'notin':
                $this->_qbuilder->where_not_in($key, $value);
        break;
        endswitch;
    }

    public function _or_where_concat($lookup, $key, $value)
    {
        switch ($lookup):
            case 'eq':
                $this->_qbuilder->or_where($key, $value);
        break;
        case 'in':
                $this->_qbuilder->or_where_in($key, $value);
        break;
        case 'gt':
                $this->_qbuilder->or_where("$key >", $value);
        break;
        case 'lt':
                $this->_qbuilder->or_where("$key <", $value);
        break;
        case 'gte':
                $this->_qbuilder->or_where("$key >=", $value);
        break;
        case 'lte':
                $this->_qbuilder->or_where("$key <=", $value);
        break;
        case 'contains':
                $this->_qbuilder->or_like($key, $value, 'both');
        break;
        case 'startswith':
                $this->_qbuilder->or_like($key, $value, 'after');
        break;
        case 'endswith':
                $this->_qbuilder->or_like($key, $value, 'before');
        break;
        case 'between':
                if (!is_array($value) || (is_array($value) && count($value) != 2)) {
                    throw new OrmExceptions(
                        sprintf("filter() usin between expected value to be an array, with two values only"));
                }
        $this->_qbuilder->or_where("$key BETWEEN $value[0] AND $value[1] ");
        break;
        case 'isnull':
                $this->_qbuilder->or_where($key, $value);
        break;
        case 'not':
                $this->_qbuilder->or_where("$key !=", $value);
        break;
        case 'notin':
                $this->_qbuilder->or_where_not_in($key, $value);
        break;
        endswitch;
    }

    public function clause($conditions)
    {
        $this->_where($this->table_name, $conditions);
    }
}
