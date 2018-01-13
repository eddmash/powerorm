<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Model;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Model\Model;

class MigrationModel extends Model
{
    public static $deferedClasses = [];

    public static function defineClass($className, $extends = '')
    {
        $use = '';
        $extendedClass = '';

        list($namespace, $className) = ClassHelper::getNamespaceNamePair($className);

        if (empty($extends) || Model::isModelBase($extends)):
            $extends = ClassHelper::getFormatNamespace(
                Model::getFullClassName(),
                true,
                false
            );
        else:
            $extendedClass = sprintf(
                '%s%s',
                ClassHelper::getFormatNamespace($namespace, true),
                $extends
            );

            $use = sprintf('use %s;', $extendedClass);

            $extends = trim(
                substr(
                    $extends,
                    strripos($extends, '\\')
                ),
                '\\'
            );
        endif;

        $class = sprintf(
            self::getTemplate(),
            sprintf('namespace %s;', $namespace),
            $use,
            $className,
            $extends
        );
        dump($class);
        $className = sprintf(
            '%s%s',
            ClassHelper::getFormatNamespace($namespace, true),
            $className
        );

        if (ArrayHelper::hasKey(self::$deferedClasses, $className)):

            foreach (self::$deferedClasses[$className] as $deferedClass) :

                self::defineClass($deferedClass['class'], $deferedClass['extends']);
            endforeach;
        endif;

        if (!ClassHelper::classExists($className, $namespace)):
            eval($class);
        endif;

        return $className;
    }

    public static function getTemplate()
    {
        return '%1$s
            %2$s
            class %3$s extends %4$s{

            }';
    }
}
