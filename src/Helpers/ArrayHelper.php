<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Helpers;

/*
 * some method borrowed from Yii\helpers\BaseArrayHelper
 * Part of the Yii framework.
 *
 * @version    2.0
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

use Eddmash\PowerOrm\Exception\KeyError;

/**
 * Class Arrays.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ArrayHelper
{
    const STRICT = 'ARRAYHELPERTHROWERROR';

    /**
     * Returns a value indicating whether the given array is an associative array.
     *
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     *
     * Note that an empty array will NOT be considered associative.
     *
     * @param array $array the array being checked
     * @param bool $allStrings whether the array keys must be all strings in order for
     *                          the array to be treated as associative
     *
     * @since 1.1.0
     *
     * @return bool whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        } else {
            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Return the value for key if key is in the array, else default. If default is not given, it defaults to null.
     * This never raise 'PHP Notice:  Undefined index: '.
     *
     * @param      $haystack
     * @param      $key
     * @param null $default
     *
     * @return mixed
     *
     * @throws KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getValue($haystack, $key, $default = null)
    {
        if (isset($haystack[$key])) {
            return $haystack[$key];
        }
        if (self::STRICT === $default) {
            throw new KeyError(sprintf('%s does not exist', $key));
        }

        return $default;
    }

    /**
     * Remove and return the value for key if key is in the array, else default. If default is not given, raise key
     * error
     * This never raise 'PHP Notice:  Undefined index: '.
     *
     * @param      $haystack
     * @param      $key
     * @param null $default
     *
     * @return mixed
     *
     * @throws KeyError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function pop(&$haystack, $key, $default = self::STRICT)
    {
        if (array_key_exists($key, $haystack)) {
            $value = $haystack[$key];
            unset($haystack[$key]);

            return $value;
        } elseif (self::STRICT !== $default) {
            return $default;
        }

        throw new KeyError(sprintf(' %s does not exist in the array', $key));
    }

    /**
     * Checks if  a key exists in the provided array.
     *
     * @param $haystack
     * @param $key
     *
     * @return bool
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function hasKey($haystack, $key)
    {
        return array_key_exists($key, $haystack);
    }

    public static function isEmpty($array)
    {
        array_filter($array);
        $empty = empty($array);

        if (!$empty) {
            array_walk_recursive(
                $array,
                function ($item) use (&$empty) {
                    if (!empty($item)) {
                        $empty = false;
                    }
                }
            );
        }

        return $empty;
    }
}
