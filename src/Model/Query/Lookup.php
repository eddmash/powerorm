<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM.
 */
namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Helpers\Tools;

/**
 * Class Filter.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Lookup
{
    public static $and = 'and';
    public static $or = 'or';
    /**
     * Lookup options.
     *
     * @internal
     *
     * @var array
     */
    protected static $lookuOptions = [
        'eq' => ' = %s',
        'in' => ' in (%s)',
        'gt' => ' > %s',
        'lt' => ' < %s',
        'gte' => ' >= %s',
        'lte' => ' <= %s',
        'contains' => ' like %s',
        'icontains' => ' ilike %s',
        'startswith' => ' like %s',
        'istartswith' => ' ilike %s',
        'endswith' => ' like %s ',
        'iendswith' => ' ilike %s ',
        'isnull' => '%s is null',
        'not' => 'not %s',
        'notin' => ' not in %s',
        'range' => ' BETWEEN %s and %s',
    ];

    protected static $lookup_pattern = '/(?<=\w)__[!?.]*/';
    protected static $where_concat_pattern = '/^~[.]*/';

    public static function validateLookup($lookup)
    {
        if (!empty($lookup) && !array_key_exists($lookup, self::$lookuOptions)):
            throw new LookupError(
                sprintf('`%1$s` is not a valid lookup parameter the options are %2$s',
                    $lookup, Tools::stringify(array_keys(self::$lookuOptions))));
        endif;
    }

    /**
     * @param $tableName
     * @param $conditions
     *
     * @return array
     *
     * @throws LookupError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function filters(QueryBuilder $queryBuilder, $conditions)
    {
        // default lookup is equal
        $lookup = 'eq';

        // we add the or conditions afterwards to avoid them being mistaken for "and" conditions when they come first
        $or_combine = [];
        $and_combine = [];

        // create where clause from the conditions given
        foreach ($conditions as $condition) :

            foreach ($condition as $key => $value) :
                $column = self::getLookupColumn($key);
                $lookup = self::getLookUP($key);
                $value = self::prepareValue($value, $lookup);
                echo self::$lookuOptions[$lookup].'<br>';
                echo $queryBuilder->createNamedParameter($value).'<br>';
                echo $value.'<br>';
                $lookupCondition = sprintf(self::$lookuOptions[$lookup], $queryBuilder->createNamedParameter($value));

                $queryString = sprintf('%s %s', $column, $lookupCondition);
                if(self::combine($key) === self::$or):
                    $queryBuilder->orWhere($queryString);
                else:
                    $queryBuilder->andWhere($queryString);
                endif;
            endforeach;

        endforeach;

    }

    public static function getLookUP($key)
    {
        $lookup = 'eq';

        // check which where clause to use
        if (preg_match(self::$lookup_pattern, $key)):
            $options = preg_split(self::$lookup_pattern, $key);
            $key = $options[0];
            $lookup = strtolower($options[1]);
        else:
            $key = sprintf('%s__%s', $key, $lookup);

            return self::getLookUP($key);
        endif;

        // validate lookups
        self::validateLookup($lookup);

        return $lookup;
    }

    public static function combine($key)
    {

        // determine how to combine where statements
        $use_or = preg_match(self::$where_concat_pattern, $key);

        // get the actual key
        if ($use_or):
            return self::$or;
        endif;

        return self::$and;
    }

    public static function getLookupColumn($key)
    {
        if (preg_match(self::$lookup_pattern, $key)):
            $match = preg_split(self::$lookup_pattern, $key);

            $key = reset($match);
        endif;

        if(self::combine($key) === self::$or):
            $key = preg_split(self::$where_concat_pattern, $key);
            $key = end($key);
        endif;

        return $key;
    }

    public static function prepareValue($value, $lookup) {
        if(in_array($lookup, ['contains', 'icontains'])):
            $value = sprintf('%%%s%%', $value);
        elseif(in_array($lookup, ['startswith', 'istartswith'])):
            $value = sprintf('%s%%', $value);
        elseif(in_array($lookup, ['endswith', 'iendswith'])):
            $value = sprintf('%%%s', $value);
        elseif($lookup === 'in' && is_array($value)):
            var_dump(Tools::stringify($value));
            $value = sprintf('%s', implode(',', $value));
        endif;

        return $value;
    }
}
