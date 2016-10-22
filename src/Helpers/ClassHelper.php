<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Helpers;

use Eddmash\PowerOrm\Exception\ClassNotFoundException;

/**
 * A Helper class for dealing with common class related tasks.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ClassHelper
{
    /**
     * Gets the class name of a class defined in a file.
     *
     * @param $file
     *
     * @return string
     */
    public static function getClassNameFromFile($file, $classesDir, $classNamespace = '')
    {
        $className = substr($file, strlen($classesDir)); // get path after the model directory
        $className = preg_replace("/^\//", '', $className); // remove any forward slash at the begining
        $className = preg_replace("/\//", '\\', $className); // change forward slash to backslash slash

        $className = preg_replace('/.php$/', '', $className); // remove extension.

        return self::classExists($className, $classNamespace);
    }

    /**
     * Gets a class name from the namespace by trimming out the namespace from the classname.
     *
     * @param $className
     * @param $namespace
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getNameFromNs($className, $namespace)
    {
        $className = static::getFormatNamespace($className, true, true);
        $namespace = static::getFormatNamespace($namespace, true, true);
        if (StringHelper::startsWith($className, $namespace)):
            $className = substr($className, strlen($namespace));
        endif;

        return trim($className, '\\');
    }

    /**
     * Format a namespace to have a  leading or a closing backslash or both.
     *
     * @param $namespace
     * @param bool|false $leadingBackslash
     * @param bool|true  $closingBackslash
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getFormatNamespace($namespace, $leadingBackslash = false, $closingBackslash = true)
    {
        $namespace = trim($namespace, '\\');

        // if it does not end with a backslash add it.
        if ($closingBackslash):
            $namespace = sprintf('%s\\', $namespace);
        endif;

        // if it does not start with a backslash add it.
        if ($leadingBackslash):
            $namespace = sprintf('\\%s', $namespace);
        endif;

        return $namespace;
    }

    /**
     * checks of a class exists on the global scope try the namespace.
     *
     * @param $className
     *
     * @return string
     *
     * @throws ClassNotFoundException
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function classExists($className, $namespace)
    {
        $orgClassName = $className;

        if (class_exists($className)):
            return $className;
        endif;

        // add namespace
        $className = sprintf('%s%s', static::getFormatNamespace($namespace, true), $className);

        if (class_exists($className)):
            return $className;
        endif;

        return false;
    }
}
