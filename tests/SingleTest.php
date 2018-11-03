<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 11/3/18
 * Time: 4:13 AM.
 */

namespace Eddmash\PowerOrm\Tests;

use function Eddmash\PowerOrm\Model\Query\Expression\q_;
use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Tests\TestApp\Models\Order;
use Eddmash\PowerOrm\Tests\TestApp\Models\OrderItem;
use Eddmash\PowerOrm\Tests\TestApp\Models\Product;
use Eddmash\PowerOrm\Tests\TestApp\Models\User;

class SingleTest extends PowerormTest
{
    public function testSqlReverseM2MFiltering()
    {
        // ********* Scalar Queryset reverse via manually defined Through model
        $query = new Query(new Product());
        $query->addQ(q_(['order' => Order::objects()->filter(['id' => 1])]));

        $expected = 'SELECT testapp_product.name, testapp_product.price, testapp_product.description, '.
            'testapp_product.stock, testapp_product.unit_of_measure, testapp_product.treshhold, '.
            'testapp_product.visible, testapp_product.image, testapp_product.owner_id, testapp_product.id '.
            'FROM testapp_product  '.
            'INNER JOIN testapp_orderitem ON ( testapp_product.id = testapp_orderitem.product_id) '.
            'WHERE testapp_orderitem.order_id = '.
            '( SELECT testapp_order.id FROM testapp_order  WHERE testapp_order.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);
    }

//
//    public function testSqlSpanRelationshipFilterByNotPkField()
//    {
//        // *************** Queryset
//        $query = new Query(new OrderItem());
//        $query->addQ(q_(['order__buyer__email' => User::objects()->filter(['email' => 'a@df.com'])]));
//
//        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, ' .
//            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id ' .
//            'FROM testapp_orderitem  ' .
//            'INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) ' .
//            'INNER JOIN testapp_user ON ( testapp_order.buyer_id = testapp_user.id) ' .
//            'WHERE testapp_user.email = ' .
//            '( SELECT testapp_user.email FROM testapp_user  WHERE testapp_user.email = ? )';
//        $this->assertQuery($query, [$expected, ['a@df.com']]);
//    }
}
