<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/17/16
 * Time: 6:01 AM.
 */
namespace eddmash\powerorm\helpers;

/**
 * Boolean helper class
 * Class Bools.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Bools
{
    /**
     * Test if value is boolean false.
     *
     * @param $value
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function false($value)
    {
        return $value === false;
    }

    /**
     * Test if value is set to a boolean true.
     *
     * @param $value
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function true($value)
    {
        return $value === true;
    }
}
