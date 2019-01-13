<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\QueryApp\Models;

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
