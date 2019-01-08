<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Components;

use Eddmash\PowerOrm\Helpers\ClassHelper;
use ReflectionClass;

abstract class AppComponent extends Component implements AppInterface
{
    public function getNamespace()
    {
        $name = get_class($this);
        list($namespace, $name) = ClassHelper::getNamespaceNamePair($name);

        return ClassHelper::getFormatNamespace(
            $namespace,
            true,
            false
        );
    }

    public function getModelsPath()
    {
        return dirname($this->getFileName()).'/Models';
    }

    public function getMigrationsPath()
    {
        return dirname($this->getFileName()).'/Migrations';
    }

    public function getDbPrefix()
    {
        return $this->getName();
    }

    private function getFileName()
    {
        $reflector = new ReflectionClass(static::class);

        return $reflector->getFileName();
    }
}
