<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

use Eddmash\PowerOrm\Helpers\Tools;

/**
 * Base class for powerorm classes, provides some base functionalities to classes that inherit from it.
 * Class Object.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Object
{
    /**
     * @var bool
     */
    private $_signal = false;

    /**
     * Returns the fully qualified name of this class.
     *
     * @return string the fully qualified name of this class
     */
    public static function getFullClassName()
    {
        return get_called_class();
    }

    public function getShortClassName()
    {
        $name = get_class($this);
        if (strpos($name, '\\')) {
            $name = explode('\\', $name);
            if (is_array($name)) {
                $name = array_pop($name);
            }
        }

        return $name;
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     *
     * @param string $name the method name
     *
     * @return bool whether the method is defined
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    /**
     * Checks if the provided propert exists in the current object.
     *
     * @param $name
     *
     * @return bool
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function hasProperty($name)
    {
        return property_exists($this, $name);
    }

    /**
     * A normalizes key names to all lowercase.
     *
     * @param $name
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function normalizeKey($name)
    {
        return Tools::normalizeKey($name);
    }

    /**
     * Dispatch signals if the Dispatch library is loaded.
     *
     * @param $signal_name
     * @param $object
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function dispatchSignal($signal_name, $object)
    {
        if ($this->_signal) {
            $this->signal->dispatch($signal_name, $object);
        }
    }

    /**
     * Returns the ReflectionClass of  the immediate parent of this object.
     *
     * @return \ReflectionClass
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getParent()
    {
        return (new \ReflectionObject($this))->getParentClass();
    }

    /**
     * Returns all the parents for this object static with the youngest to the oldest
     * The resolution order to follow when going up a inheritance hierarchy.
     *
     * @return array this an array of ReflectionClass
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getParents()
    {
        $reflectionClass = new \ReflectionObject($this);

        $parents = [];
        while ($reflectionClass->getParentClass()):
            $reflectionClass = $reflectionClass->getParentClass();
            $parents[$reflectionClass->getName()] = $reflectionClass;
        endwhile;

        return $parents;
    }
}
