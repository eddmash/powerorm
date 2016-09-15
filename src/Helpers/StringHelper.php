<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/18/16
 * Time: 2:23 PM.
 */
namespace Eddmash\PowerOrm\Helpers;

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
class StringHelper
{
    /**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     *
     * @param string $string the string being measured for length
     *
     * @return int the number of bytes in the given string
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Check if given string starts with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $string        Input string
     * @param string $with          Part to search
     * @param bool   $caseSensitive Case sensitive search. Default is true
     *
     * @return bool Returns true if first input starts with second input, false otherwise
     */
    public static function startsWith($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byteLength($with)) {
            return true;
        }
        if ($caseSensitive) {
            return strncmp($string, $with, $bytes) === 0;
        } else {
            return mb_strtolower(
                mb_substr($string, 0, $bytes, '8bit'), self::getCharset()) === mb_strtolower($with, self::getCharset());
        }
    }

    /**
     * Check if given string ends with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $string
     * @param string $with
     * @param bool   $caseSensitive Case sensitive search. Default is true
     *
     * @return bool Returns true if first input ends with second input, false otherwise
     */
    public static function endsWith($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byteLength($with)) {
            return true;
        }
        if ($caseSensitive) {
            // Warning check, see http://php.net/manual/en/function.substr-compare.php#refsect1-function.substr-compare-returnvalues
            if (static::byteLength($string) < $bytes) {
                return false;
            }

            return substr_compare($string, $with, -$bytes, $bytes) === 0;
        } else {
            return mb_strtolower(
                mb_substr($string, -$bytes, null, '8bit'), self::getCharset()) === mb_strtolower($with,
                self::getCharset());
        }
    }

    public static function getCharset()
    {
        //todo make character set independent of framework
        return config_item('charset');
    }

    public static function camelToSpace($name)
    {
        return static::camelReplace($name, ' ');
    }

    public static function camelToUnderscore($name)
    {
        return static::camelReplace($name, '_');
    }

    public static function camelReplace($name, $delim = '')
    {
        return preg_replace('/(?=[A-Z])/', $delim, $name);
    }

    public static function isValidVariableName($name) {
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name);
    }
}