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
 * Takes a series of content that need to be properly indented for
 * writing on disk.
 *
 * @since  1.1.0
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
    private $indentation = 0;

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
     * @since  1.1.0
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
     * @since  1.1.0
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduceIndent()
    {
        if ($this->indentation > 0):
            --$this->indentation;
        endif;
    }

    /**
     * Does the actual indentation of the content.
     *
     * @param int $by
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function indent($by = 1)
    {
        $tab = "\t";

        return str_repeat($tab, $by);
    }

    private static function handleAssocArray(
        self $content,
        $key,
        $val,
        &$import
    ) {
        $key_arr = static::forceString($key);

        if (is_array($val)):

            $content->addIndent();
            $content->addItem(
                sprintf(
                    '%1$s=>[ ',
                    $key_arr[0]
                )
            );

            foreach ($val as $val_key => $in_val) :
                if ($in_val instanceof DeConstructableInterface):
                    $in_key = self::forceString($val_key);
                    static::handleDeconstructable($content, $in_key[0], $in_val, $import);
                else:
                    $val_arr = static::forceString($in_val);
                    $import = array_merge($import, $val_arr[1]);

                    $content->addIndent();
                    $content->addItem(
                        sprintf(
                            "'%1\$s'=> %2\$s,",
                            $val_key,
                            $val_arr[0]
                        )
                    );
                    $content->reduceIndent();
                endif;

            endforeach;

            $content->addItem('],');
            $content->reduceIndent();
        elseif ($val instanceof DeConstructableInterface):

            self::handleDeconstructable($content, $key_arr[0], $val, $import);
        else:
            $val_arr = static::forceString($val);
            $content->addIndent();
            $content->addItem(
                sprintf(
                    '%1$s=> %2$s,',
                    $key_arr[0],
                    $val_arr[0]
                )
            );
            $content->reduceIndent();
        endif;

        // imports
        if (!empty($key_arr[1])):
            $import = array_merge($import, $key_arr[1]);
        endif;

        if (!empty($val_arr[1])):
            $import = array_merge($import, $val_arr[1]);
        endif;
    }

    private static function handleDeconstructable(
        self $content,
        $key,
        DeConstructableInterface $val,
        &$import
    ) {
        $desc_skel = $val->deconstruct();

        $desc_import = [$desc_skel['path']];

        $desc_class = $desc_skel['name'];

        $desc_constructor_args = [
            $desc_skel['constructorArgs'],
        ];

        $content->addIndent();
        $content->addItem(
            sprintf(
                '%s=> %s([',
                $key,
                $desc_class
            )
        );

        foreach ($desc_constructor_args as $arg) :
            foreach ($arg as $desc_key => $desc_val) :
                self::handleAssocArray(
                    $content,
                    $desc_key,
                    $desc_val,
                    $desc_import
                );
            endforeach;
        endforeach;
        $content->addItem(']),');
        $content->reduceIndent();
        $import = array_merge($import, $desc_import);
    }

    /**
     * Converts the buffer into an actual string.
     *
     * @return string
     *
     * @since  1.1.0
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function formatObject(DeconstructableObject $object)
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
                    $content->addItem('[],');
                else:
                    $content->addItem('[');

                    foreach ($arg as $key => $val) :
                        if (!is_int($key)):
                            static::handleAssocArray($content, $key, $val, $import);
                        else:

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

                endif;
            else:
                $val_array = static::forceString($arg);
                $content->addItem(sprintf(' %s,', $val_array[0]));

            endif;

            if (!empty($val_array[1])):
                $import = array_merge($import, $val_array[1]);
            endif;
        endforeach;

        $string = implode(PHP_EOL, $content->buffer);

        $string = trim($string, ',');

        return [
            sprintf(
                "%1\$s::createObject(%2\$s\t\t\t)",
                $class,
                PHP_EOL.$string.PHP_EOL
            ),
            $import,
        ];
    }

    /**
     * Converts a value to a string ready for writing on a file.
     *
     * @param $value
     *
     * @return array
     *
     * @since  1.1.0
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

                    array_push(
                        $assoc,
                        sprintf(
                            '%1$s=> %2$s',
                            $key_arr[0],
                            $val_arr[0]
                        )
                    );

                    if (!empty($key_arr[1])):
                        $import = array_merge($import, $key_arr[1]);
                    endif;

                    if (!empty($val_arr[1])):
                        $import = array_merge($import, $val_arr[1]);
                    endif;
                else:

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

            if (empty($constructor_args[0])):
                $cons_args = '';
            else:
                $cons_args = implode(',', $cons_args);
            endif;

            return [
                sprintf(
                    '%s(%s)',
                    $class,
                    $cons_args
                ),
                $import,
            ];
        endif;

        if (true === $value):
            return ['true', []];
        endif;

        if (false === $value):
            return ['false', []];
        endif;

        if (null === $value):
            return ['null', []];
        endif;

        return [$value, []];
    }
}
