<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\TestingApps\AutodetectorTest\Models;

use Eddmash\PowerOrm\Model\Model;


/**
 * Class User
 * @package App\Models
 */
class UserEmpty extends Model
{

    public function unboundFields()
    {
        return [
        ];
    }

}