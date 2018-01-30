<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 11:55 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

use Eddmash\PowerOrm\Model\Model;

class NotAModel
{
    public function unboundFields()
    {
        return [
            'name' => Model::CharField(['maxLength' => 200]),
            'email' => Model::EmailField(),
        ];
    }
}
