<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM.
 */
namespace Eddmash\PowerOrm\Model\Query;

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
    /**
     * Lookup options.
     *
     * @internal
     *
     * @var array
     */
    protected static $lookuOptions = [
        'eq' => ' = %s',
        'in' => ' in %s',
        'gt' => ' > %s',
        'lt' => ' < %s',
        'gte' => ' >= %s',
        'lte' => ' <= %s',
        'contains' => ' like %%s% ',
        'icontains' => ' ilike % %s% ',
        'startswith' => ' like %s% ',
        'istartswith' => ' ilike %s% ',
        'endswith' => '  %%s like  ',
        'iendswith' => ' %%s ilike ',
        'isnull' => '%s is null',
        'not' => 'not %s',
        'notin' => ' not in %s',
        'range' => ' BETWEEN %s and %s',
    ];

    protected static $lookup_pattern = '/__/';
    protected static $where_concat_pattern = '/^~[.]*/';

    public static function validateLookup($lookup)
    {
        if (!empty($lookup) && !array_key_exists($lookup, self::$lookuOptions)):
            throw new LookupError(
                sprintf('`%1$s` is not a valid lookup parameter the options are %2$s',
                    $lookup, Tools::stringify(self::$lookuOptions)));
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
    public static function lookUp($tableName, $conditions)
    {
        // default lookup is equal
        $lookup = 'eq';

        // we add the or conditions afterwards to avoid them being mistaken for "and" conditions when they come first
        $or_combine = [];
        $and_combine = [];

        // create where clause from the conditions given
        foreach ($conditions as $condition) :

            foreach ($condition as  $key => $value) :

                $tableName = strtolower($tableName);

                // append table name to key
                if (!empty($tableName)):
                    $key = $tableName.".$key";
                endif;

                // check if we need to use OR to combine
//                if ($use_or):
//                    $or_combine[] = [sprintf(self::$lookuOptions[$lookup], $value)];
//                else:
//                    // otherwise use "and"
//                    $and_combine[] = [sprintf(self::$lookuOptions[$lookup], $value)];

//                endif;
            endforeach;

        endforeach;

        return [$and_combine, $or_combine];
    }

    public static function getLookUP($key) {
        $lookup = 'eq';
        // check which where clause to use
        if (preg_match(self::$lookup_pattern, $key)):
            $options = preg_split(self::$lookup_pattern, $key);
            $key = $options[0];
            $lookup = strtolower($options[1]);
        endif;

        // validate lookups
        self::validateLookup($lookup);

        return $lookup;
    }

    public static function combine($key) {

        // determine how to combine where statements
        $use_or = preg_match(self::$where_concat_pattern, $key);

        // get the actual key
        if ($use_or):
            return ' || ';
        endif;

        return ' && ';
    }

    public static function getLookupColumn($key) {
        if(preg_match(self::$lookup_pattern, $key)):
            return reset(preg_split(self::$lookup_pattern, $key));
        endif;

        return $key;
    }
}
