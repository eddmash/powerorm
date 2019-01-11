<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\RegistryApp\Models;

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
