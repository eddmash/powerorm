<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
