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

class Product extends Model
{
    public function unboundFields()
    {
        return [
            'name' => Model::CharField(['maxLength' => 30]),
            'price' => Model::DecimalField(['maxDigits' => 9, 'decimalPlaces' => 2]),
            'description' => Model::TextField(['null' => true]),
            'stock' => Model::DecimalField(['maxDigits' => 9, 'decimalPlaces' => 2]),
            'unit_of_measure' => Model::CharField(['maxLength' => 30]),
            'treshhold' => Model::DecimalField(['maxDigits' => 9, 'decimalPlaces' => 5]),
            'visible' => Model::BooleanField(),
            'image' => Model::CharField(['maxLength' => 150]),
            'owner' => Model::ForeignKey(['to' => User::class]),
            'created_by' => Model::ForeignKey(['to' => User::class]),
        ];
    }
}
