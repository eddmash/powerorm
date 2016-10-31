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
        'notin',
    ];

    public static function validateLookup($lookup)
    {
        if (!empty($lookup) && !in_array($lookup, self::$lookuOptions)):
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
        $lookup_pattern = '/__/';
        $where_concat_pattern = '/^~[.]*/';

        // we add the or conditions afterwards to avoid them being mistaken for "and" conditions when they come first
        $or_combine = [];
        $and_combine = [];

        // create where clause from the conditions given
        foreach ($conditions as $condition) :

            foreach ($condition as  $key => $value) :
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
                self::validateLookup($lookup);

                $tableName = strtolower($tableName);

                // append table name to key
                if (!empty($tableName)):
                    $key = $tableName.".$key";
                endif;

                // check if we need to use OR to combine
                if ($use_or):
                    $or_combine[] = ['lookup' => $lookup, 'key' => $key, 'value' => $value];
                else:
                    // otherwise use "and"
                    $and_combine[] = ['lookup' => $lookup, 'key' => $key, 'value' => $value];

                endif;
            endforeach;

        endforeach;

        return [$and_combine, $or_combine];
    }
}
