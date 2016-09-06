<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/18/16
 * Time: 2:23 PM.
 */
namespace powerorm\helpers;

/**
 * some method borrowed from Yii\helpers\BaseStringHelper
 * Part of the Yii framework.
 *
 * @version    2.0
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alex Makarov <sam@rmcreative.ru>
 *
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
/**
 * Class Strings.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Strings
{
    /**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     *
     * @param string $string the string being measured for length
     *
     * @return int the number of bytes in the given string.
     */
    public static function byte_length($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Check if given string starts with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $string        Input string
     * @param string $with          Part to search
     * @param bool   $caseSensitive Case sensitive search. Default is true.
     *
     * @return bool Returns true if first input starts with second input, false otherwise
     */
    public static function starts_with($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byte_length($with)) {
            return true;
        }
        if ($caseSensitive) {
            return strncmp($string, $with, $bytes) === 0;
        } else {
            return mb_strtolower(
                mb_substr($string, 0, $bytes, '8bit'), self::get_charset()) === mb_strtolower($with, self::get_charset());
        }
    }

    /**
     * Check if given string ends with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $string
     * @param string $with
     * @param bool   $caseSensitive Case sensitive search. Default is true.
     *
     * @return bool Returns true if first input ends with second input, false otherwise
     */
    public static function ends_with($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byte_length($with)) {
            return true;
        }
        if ($caseSensitive) {
            // Warning check, see http://php.net/manual/en/function.substr-compare.php#refsect1-function.substr-compare-returnvalues
            if (static::byte_length($string) < $bytes) {
                return false;
            }

            return substr_compare($string, $with, -$bytes, $bytes) === 0;
        } else {
            return mb_strtolower(
                mb_substr($string, -$bytes, null, '8bit'), self::get_charset()) === mb_strtolower($with, self::get_charset());
        }
    }

    public static function get_charset()
    {
        return config_item('charset');
    }
}
