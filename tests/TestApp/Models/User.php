<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 8/16/18
 * Time: 10:37 AM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

use Eddmash\PowerOrm\Model\Model;

class User extends Model
{
    public function unboundFields()
    {
        return [
            'first_name' => Model::CharField(['maxLength' => 30]),
            'last_name' => Model::CharField(['maxLength' => 30]),
            'password' => Model::CharField(['maxLength' => 249]),
            'email' => Model::CharField(['maxLength' => 249]),
            'phone' => Model::CharField(['maxLength' => 30]),
        ];
    }

    public function __toString()
    {
        return $this->first_name;
    }
}
