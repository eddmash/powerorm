<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:26 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

class ConcreteModel extends AbstractModel
{
    public function unboundFields()
    {
        return [
            'town' => \Eddmash\PowerOrm\Model\Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}
