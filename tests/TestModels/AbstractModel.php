<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:24 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

abstract class AbstractModel extends \Eddmash\PowerOrm\Model\Model
{
    public function unboundFields()
    {
        return [
            'country' => \Eddmash\PowerOrm\Model\Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
            'school' => \Eddmash\PowerOrm\Model\Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}
