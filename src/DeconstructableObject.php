<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

abstract class DeconstructableObject extends BaseObject implements DeConstructableInterface
{
    private $constructorArgs;

    const DEBUG_IGNORE = [];

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        return $this->constructorArgs;
    }

    /**
     * {@inheritdoc}
     */
    public static function createObject($config = [])
    {
        $instance = (empty($config)) ? new static() : new static($config);

        if ($instance instanceof self) {
            $instance->setConstructorArgs($config);
        }

        return $instance;
    }

    /**
     * Set the arguments passed to the constructor.
     *
     * @param mixed $constructorArgs
     */
    public function setConstructorArgs($constructorArgs)
    {
        $this->constructorArgs = $constructorArgs;
    }

    public function __debugInfo()
    {
//        $meta = [];
//        foreach (get_object_vars($this) as $name => $value) {
//            if (in_array($name, $this->getIgnored())) {
//                continue;
//            }
//            $meta[$name] = $value;
//        }

        return get_object_vars($this);
    }

    public function getIgnored()
    {
        return array_merge(static::DEBUG_IGNORE, ['constructorArgs']);
    }
}
