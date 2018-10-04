<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 8/16/18
 * Time: 2:49 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

use Eddmash\PowerOrm\Model\Model;

class OrderItem extends Model
{
    public function unboundFields()
    {
        return [
            'order' => Model::ForeignKey(['to' => Order::class]),
            'product' => Model::ForeignKey(['to' => Product::class]),
            'qty' => Model::DecimalField(['maxDigits' => 9, 'decimalPlaces' => 2]),
            'price' => Model::DecimalField(['maxDigits' => 9, 'decimalPlaces' => 2]),
        ];
    }
}
