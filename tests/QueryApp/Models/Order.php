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

class Order extends Model
{
    public function unboundFields()
    {
        return [
            'products' => Model::ManyToManyField(['to' => Product::class,
                'through' => OrderItem::class, 'relatedName' => 'orders', ]),
            'buyer' => Model::ForeignKey(['to' => User::class]),
            'date' => Model::DateField(['autoAddNow' => true]),
        ];
    }
}
