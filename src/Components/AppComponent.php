<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 12/28/17
 * Time: 10:36 AM.
 */

namespace Eddmash\PowerOrm\Components;

use Eddmash\PowerOrm\BaseOrm;
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
        return BaseOrm::getDbPrefix();
    }

    private function getFileName()
    {
        $reflector = new ReflectionClass(static::class);

        return $reflector->getFileName();
    }
}
