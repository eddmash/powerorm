<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:26 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

class DirectConcreateBaseProxy extends ConcreteModel
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}
