<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 10/4/18
 * Time: 8:18 AM.
 */

namespace Eddmash\PowerOrm\Tests\Model\Query;

use function Eddmash\PowerOrm\Model\Query\Expression\not_;
use function Eddmash\PowerOrm\Model\Query\Expression\q_;
use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Tests\PowerormDbTest;
use Eddmash\PowerOrm\Tests\TestApp\Models\Author;
use Eddmash\PowerOrm\Tests\TestApp\Models\OrderItem;
use Eddmash\PowerOrm\Tests\TestApp\Models\User;

class QueryTest extends PowerormDbTest
{
    public function testSqlFetchAll()
    {
        $query = new Query(new Author());
        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author';

        $this->assertQuery($query, [$expected, []]);
    }

    public function testSqlFetchFiltered()
    {
        $query = new Query(new Author());
        $query->addQ(q_(['email' => 'p']));
        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'WHERE testapp_author.email = ?';

        $this->assertQuery($query, [$expected, ['p']]);
    }

    public function testSqlFetchExclude()
    {
        $query = new Query(new Author());
        $query->addQ(not_(['email' => 'p']));
        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'WHERE NOT (testapp_author.email = ?)';

        $this->assertQuery($query, [$expected, ['p']]);
    }

    public function testSqlNotOr()
    {
        $query = new Query(new Author());
        $query->addQ(not_(['email' => 'p'])->or_(['email' => 'o']));
        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'WHERE (testapp_author.email = ? OR NOT (testapp_author.email = ?))';

        $this->assertQuery($query, [$expected, ['o', 'p']]);
    }

    public function testSqlAndOr()
    {
        $query = new Query(new Author());
        $query->addQ(not_(['email' => 'p'])->and_(['email' => 'o']));
        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'WHERE (testapp_author.email = ? AND NOT (testapp_author.email = ?))';

        $this->assertQuery($query, [$expected, ['o', 'p']]);
    }

    public function testSqlForwardForeignKey()
    {
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'WHERE testapp_orderitem.order_id = ?';

        $this->assertQuery($query, [$expected, [1]]);
    }

    public function testSqlForeignKeyJoinFilterByPKField()
    {
        // ********* Scalar value
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order__buyer' => 1]));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) '.
            'WHERE testapp_order.buyer_id = ?';
        $this->assertQuery($query, [$expected, [1]]);

        // *************** Queryset
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order__buyer' => User::objects()->filter(['id' => 30])]));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) '.
            'WHERE testapp_order.buyer_id = '.
            '( SELECT `testapp_user`.`id` FROM `testapp_user`  WHERE `testapp_user`.`id` = ? )';
        $this->assertQuery($query, [$expected, [30]]);
    }

//
//    public function testSqlForeignKeyJoinFilterByNotPkField()
//    {
//        $query = new Query(new OrderItem());
//        $query->addQ(q_(['order__buyer__email' => "a@df.com"]));
//
//        $expected = "SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, " .
//            "testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id " .
//            "FROM testapp_orderitem  " .
//            "INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) " .
//            "INNER JOIN testapp_user ON ( testapp_order.buyer_id = testapp_user.id) " .
//            "WHERE testapp_user.email = ?";
//
//        $this->assertQuery($query, [$expected, ["a@df.com"]]);
//
//
//        // *************** Queryset
//        $query = new Query(new OrderItem());
//        $query->addQ(q_(['order__buyer__email' => User::objects()->filter(['email'=>"a@df.com"])]));
//
//        $expected = "SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, ".
//            "testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id ".
//            "FROM testapp_orderitem  ".
//            "INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) ".
//            "WHERE testapp_order.buyer_id = ".
//            "( SELECT `testapp_user`.`id` FROM `testapp_user`  WHERE `testapp_user`.`id` = ? )";
//        $this->assertQuery($query, [$expected, ["a@df.com"]]);
//
//    }
}
