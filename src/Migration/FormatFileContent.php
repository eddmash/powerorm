<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\DeConstructableInterface;
use Eddmash\PowerOrm\DeconstructableObject;

/**
 * Takes a series of content that need to be properly indented for writing on disk.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FormatFileContent
{
    /**
     * Holds the formated content.
     *
     * @var array
     */
    private $buffer = [];

    /**
     * the number of indents to add to content.
     *
     * @var int
     */
    private $indentation;

    public function __construct($indentation = 0)
    {
        $this->indentation = $indentation;
    }

    public static function createObject($indentation = 0)
    {
        return new static($indentation);
    }

    /**
     * Add content to be formated.
     *
     * @param $item
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addItem($item)
    {
        $indentation = $this->indent($this->indentation);

        $this->buffer[] = $indentation.$item;
    }

    /**
     * adds indentation to content.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addIndent()
    {
        ++$this->indentation;
    }

    /**
     * Reduces indentation on content.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduceIndent()
    {
        --$this->indentation;
    }

    /**
     * Does the actual indentation of the content.
     *
     * @param int $by
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function indent($by = 1)
    {
        $tab = "\t";

        return str_repeat($tab, $by);
    }

    /**
     * Converts the buffer into an actual string.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        return implode("\n", $this->buffer);
    }

    /**
     * @param DeconstructableObject $object
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function formatObject($object)
    {
        $skel = $object->deconstruct();

        $path = '';
        $name = '';
        $constructorArgs = [];
        //unpack the array to set the above variables with actual values.
        extract($skel);

        $import = [$path];

        $class = $name;

        $content = self::createObject(4);
        $constructorArgs = [$constructorArgs];
        foreach ($constructorArgs as $arg) :

            if (is_array($arg)):

                if (empty($arg)):
                    $content->addItem('[],'); else:
                    $content->addItem('[');

        foreach ($arg as $key => $val) :
                        if (!is_int($key)):
                            $key_arr = static::forceString($key);

        if (is_array($val)):

                                $content->addIndent();
        $content->addItem(sprintf('%1$s=>[ ', $key_arr[0]));

        foreach ($val as $val_key => $in_val) :

                                    $val_arr = static::forceString($in_val);
        $import = array_merge($import, $val_arr[1]);

        $content->addIndent();
        $content->addItem(sprintf("'%1\$s'=> %2\$s,", $val_key, $val_arr[0]));
        $content->reduceIndent();

        endforeach;

        $content->addItem('],');
        $content->reduceIndent(); else:
                                $val_arr = static::forceString($val);
        $content->addIndent();
        $content->addItem(sprintf('%1$s=> %2$s,', $key_arr[0], $val_arr[0]));
        $content->reduceIndent();
        endif;

                            // imports
                            if (!empty($key_arr[1])):
                                $import = array_merge($import, $key_arr[1]);
        endif;

        if (!empty($val_arr[1])):
                                $import = array_merge($import, $val_arr[1]);
        endif; else:

                            $val_arr = static::forceString($val);

        $content->addIndent();
        $content->addItem(sprintf('%s', $val_arr[0]));
        $content->reduceIndent();

        if (!empty($val_arr[1])):
                                $import = array_merge($import, $val_arr[1]);
        endif;

        endif;
        endforeach;

        $content->addItem('],');

        endif; else:

                $val_array = static::forceString($arg);
        $content->addItem(sprintf(' %s,', $val_array[0]));

        endif;

        if (!empty($val_array[1])):
                $import = array_merge($import, $val_array[1]);
        endif;
        endforeach;

        $string = implode(PHP_EOL, $content->buffer);

        $string = trim($string, ',');

        return [sprintf("%1\$s::createObject(%2\$s\t\t\t)", $class, PHP_EOL.$string.PHP_EOL), $import];
    }

    /**
     * Converts a value to a string ready for writing on a file.
     *
     * @param $value
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function forceString($value)
    {
        if (is_string($value)):
            return [sprintf("'%s'", $value), []];
        endif;

        if (is_array($value)):
            $import = [];
        $assoc = [];

        foreach ($value as $key => $val) :
                if (!is_int($key)):
                    $key_arr = static::forceString($key);
        $val_arr = static::forceString($val);

        array_push($assoc, sprintf('%1$s=> %2$s', $key_arr[0], $val_arr[0]));

        if (!empty($key_arr[1])):
                        $import = array_merge($import, $key_arr[1]);
        endif;

        if (!empty($val_arr[1])):
                        $import = array_merge($import, $val_arr[1]);
        endif; else:

                    $val_arr = static::forceString($val);
        array_push($assoc, $val_arr[0]);

        if (!empty($val_arr[1])):
                        $import = array_merge($import, $val_arr[1]);
        endif;

        endif;

        endforeach;

        return [sprintf('[%s]', implode(', ', $assoc)), $import];
        endif;

        if (is_object($value) && $value instanceof DeConstructableInterface):
            $skel = $value->deconstruct();

        $import = [$skel['path']];

        $class = $skel['name'];

        $constructor_args = [$skel['constructorArgs']];
        $cons_args = [];
        foreach ($constructor_args as $arg) :

                $val_array = static::forceString($arg);

        array_push($cons_args, $val_array[0]);

        if (!empty($val_array[1])):
                    $import = array_merge($import, $val_array[1]);
        endif;
        endforeach;

        return [sprintf('%1$s::createObject(%2$s)', $class, implode(',', $cons_args)), $import];
        endif;

        if ($value === true):
            return ['true', []];
        endif;

        if ($value === false):
            return ['false', []];
        endif;

        return [$value, []];
    }
}
