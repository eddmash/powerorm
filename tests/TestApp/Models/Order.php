<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 8/17/18
 * Time: 11:04 AM.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

use Eddmash\PowerOrm\Model\Model;

class Order extends Model
{
    public function unboundFields()
    {
        return [
            'products' => Model::ManyToManyField(['to' => Product::class,
                'through' => OrderItem::class, ]),
            'buyer' => Model::ForeignKey(['to' => User::class]),
            'date' => Model::DateField(['autoAddNow' => true]),
        ];
    }
}
