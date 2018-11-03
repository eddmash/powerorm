<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 8/16/18
 * Time: 12:07 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

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
