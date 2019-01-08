<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
