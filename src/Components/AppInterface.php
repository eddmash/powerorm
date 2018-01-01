<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 12/28/17
 * Time: 10:37 AM.
 */

namespace Eddmash\PowerOrm\Components;

interface AppInterface extends ComponentInterface
{
    public function getNamespace();

    public function getModelsPath();

    public function getMigrationsPath();

    public function getDbPrefix();
}
