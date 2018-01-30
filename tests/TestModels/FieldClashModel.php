<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:24 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

class FieldClashModel extends AbstractModel
{
    public function unboundFields()
    {
        return [
            'school' => \Eddmash\PowerOrm\Model\Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}
