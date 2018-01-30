<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:26 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

class ChildConcreteWithImmediateParentProxy extends DirectConcreateBaseProxy
{
    public function unboundFields()
    {
        return [
            'child' => \Eddmash\PowerOrm\Model\Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}
