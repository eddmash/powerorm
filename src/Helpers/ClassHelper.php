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
    public static function getClassNameFromFile($file, $classesDir)
    {
        $className = substr($file, strlen($classesDir)); // get path after the model directory
        $className = preg_replace("/^\//", '', $className); // remove any forward slash at the begining
        $className = preg_replace("/\//", '\\', $className); // change forward slash to backslash slash

        $className = preg_replace('/.php$/', '', $className); // remove extension.

        return $className;
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

    /**
     * Returns all the parents for the instance from the youngest to the oldest
     * The resolution order to follow when going up a inheritance hierarchy.
     *
     * @return array this an array of ReflectionClass
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getParents($instance, $stopAt = [])
    {
        $reflectionClass = new \ReflectionObject($instance);

        $parents = [];
        while ($reflectionClass->getParentClass()):
            $reflectionClass = $reflectionClass->getParentClass();
        if (in_array($reflectionClass->getName(), $stopAt)):
                break;
        endif;
        $parents[$reflectionClass->getName()] = $reflectionClass;
        endwhile;

        return $parents;
    }

    public static function getNamespaceNamePair($name)
    {
        $namespace = '';

        $name = rtrim($name, '\\');
        if ($pos = strrpos($name, '\\')) :
            $namespace = substr($name, 0, $pos);
        $name = substr($name, $pos + 1);
        endif;

        return [$namespace, $name];
    }

    public static function file_get_php_classes($filepath)
    {
        $php_code = file_get_contents($filepath);
        $classes = self::get_php_classes($php_code);

        return $classes;
    }

    public static function get_php_classes($php_code)
    {
        $classes = array();
        $tokens = token_get_all($php_code);

        $count = count($tokens);
        for ($i = 2; $i < $count; ++$i) {
            if ($tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes;
    }

    /**
     * Gets the first class defined on a php file.
     *
     *
     * borrowed from http://jarretbyrne.com/2015/06/197/
     *
     * @param $path_to_file
     *
     * @return mixed|string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getClassFromFile($path_to_file)
    {
        //Grab the contents of the file
        $contents = file_get_contents($path_to_file);

        //Start with a blank namespace and class
        $namespace = $class = '';

        //Set helper values to know that we have found the namespace/class token and need to collect the string values after them
        $getting_namespace = $getting_class = false;

        //Go through each token and evaluate it as necessary
        foreach (token_get_all($contents) as $token) {
            //If this token is the namespace declaring, then flag that the next tokens will be the namespace name
            if (is_array($token) && T_NAMESPACE == $token[0]) {
                $getting_namespace = true;
            }

            //If this token is the class declaring, then flag that the next tokens will be the class name
            if (is_array($token) && T_CLASS == $token[0]) {
                $getting_class = true;
            }

            //While we're grabbing the namespace name...
            if (true === $getting_namespace) {
                //If the token is a string or the namespace separator...
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    //Append the token's value to the name of the namespace
                    $namespace .= $token[1];
                } elseif (';' === $token) {
                    //If the token is the semicolon, then we're done with the namespace declaration
                    $getting_namespace = false;
                }
            }

            //While we're grabbing the class name...
            if (true === $getting_class) {
                //If the token is a string, it's the name of the class
                if (is_array($token) && T_STRING == $token[0]) {
                    //Store the token's value as the class name
                    $class = $token[1];

                    //Got what we need, stope here
                    break;
                }
            }
        }

        //Build the fully-qualified class name and return it
        return $namespace ? $namespace.'\\'.$class : $class;
    }

    /**
     * Configures an object with the initial property values.
     *
     * @param object $object     the object to be configured
     * @param array  $properties the property initial values given in terms of name-value pairs
     * @param array  $map        if set the the key should be a key on the $properties and the value should a a property on
     *                           the $object to which the the values of $properties will be assigned to
     *
     * @return object the object itself
     */
    public static function setAttributes($object, $properties, $map = [])
    {
        if (empty($properties)):
            return $object;
        endif;

        foreach ($properties as $name => $value) :

            if (ArrayHelper::hasKey($map, $name)):

                $name = $map[$name];
        endif;

        $setterMethod = sprintf('set%s', ucfirst($name));

        if (method_exists($object, $setterMethod)):
                call_user_func([$object, $setterMethod], $value); elseif (property_exists($object, $name)):

                $object->{$name} = $value;
        endif;

        endforeach;

        return $object;
    }
}
