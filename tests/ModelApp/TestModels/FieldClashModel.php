<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\ModelApp\TestModels;

use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Tests\ModelApp\Models\AbstractModel;

class FieldClashModel extends AbstractModel
{
    public function unboundFields()
    {
        return [
            'school' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}
