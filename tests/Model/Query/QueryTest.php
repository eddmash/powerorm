<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\Model\Query;

use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Tests\PowerormTest;
use Eddmash\PowerOrm\Tests\TestApp\Models\Author;
use Eddmash\PowerOrm\Tests\TestApp\Models\Book;
use Eddmash\PowerOrm\Tests\TestApp\Models\Order;
use Eddmash\PowerOrm\Tests\TestApp\Models\OrderItem;
use Eddmash\PowerOrm\Tests\TestApp\Models\Product;
use Eddmash\PowerOrm\Tests\TestApp\Models\User;
use function Eddmash\PowerOrm\Model\Query\Expression\not_;
use function Eddmash\PowerOrm\Model\Query\Expression\q_;

class QueryTest extends PowerormTest
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

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSimpleForeignKey()
    {
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'WHERE testapp_orderitem.order_id = ?';

        $this->assertQuery($query, [$expected, [1]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSpanRelationshipFilterByPKField()
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
            '( SELECT testapp_user.id FROM testapp_user  WHERE testapp_user.id = ? )';
        $this->assertQuery($query, [$expected, [30]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSpanRelationshipFilterByNotPkField()
    {
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order__buyer__email' => 'a@df.com']));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) '.
            'INNER JOIN testapp_user ON ( testapp_order.buyer_id = testapp_user.id) '.
            'WHERE testapp_user.email = ?';

        $this->assertQuery($query, [$expected, ['a@df.com']]);

        // *************** Queryset
        $query = new Query(new OrderItem());
        $query->addQ(q_(['order__buyer__email' => User::objects()->filter(['email' => 'a@df.com'])]));

        $expected = 'SELECT testapp_orderitem.order_id, testapp_orderitem.product_id, '.
            'testapp_orderitem.qty, testapp_orderitem.price, testapp_orderitem.id '.
            'FROM testapp_orderitem  '.
            'INNER JOIN testapp_order ON ( testapp_orderitem.order_id = testapp_order.id) '.
            'INNER JOIN testapp_user ON ( testapp_order.buyer_id = testapp_user.id) '.
            'WHERE testapp_user.email = '.
            '( SELECT testapp_user.email FROM testapp_user  WHERE testapp_user.email = ? )';
        $this->assertQuery($query, [$expected, ['a@df.com']]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlReverseM2MFiltering()
    {
        // ********* Scalar value reverse via manually defined Through model
        $query = new Query(new Product());
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT testapp_product.name, testapp_product.price, testapp_product.description, '.
            'testapp_product.stock, testapp_product.unit_of_measure, testapp_product.treshhold, '.
            'testapp_product.visible, testapp_product.image, testapp_product.owner_id, testapp_product.created_by_id'.
            ', testapp_product.id '.
            'FROM testapp_product  '.
            'INNER JOIN testapp_orderitem ON ( testapp_product.id = testapp_orderitem.product_id) '.
            'WHERE testapp_orderitem.order_id = ?';
        $this->assertQuery($query, [$expected, [1]]);

        // ********* Scalar Queryset reverse via manually defined Through model
        $query = new Query(new Product());
        $query->addQ(q_(['order' => Order::objects()->filter(['id' => 1])]));

        $expected = 'SELECT testapp_product.name, testapp_product.price, testapp_product.description, '.
            'testapp_product.stock, testapp_product.unit_of_measure, testapp_product.treshhold, '.
            'testapp_product.visible, testapp_product.image, testapp_product.owner_id, '.
            'testapp_product.created_by_id, testapp_product.id '.
            'FROM testapp_product  '.
            'INNER JOIN testapp_orderitem ON ( testapp_product.id = testapp_orderitem.product_id) '.
            'WHERE testapp_orderitem.order_id = '.
            '( SELECT testapp_order.id FROM testapp_order  WHERE testapp_order.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);

        // ********* Scalar reverse via autocreated Through model
        $query = new Query(new Author());
        $query->addQ(q_(['book' => 1]));

        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'INNER JOIN testapp_book_author ON ( testapp_author.id = testapp_book_author.author_id) '.
            'WHERE testapp_book_author.book_id = ?';
        $this->assertQuery($query, [$expected, [1]]);
        // ********* Queryset reverse via autocreated Through model
        $query = new Query(new Author());
        $query->addQ(q_(['book' => Book::objects()->filter(['id' => 1])]));

        $expected = 'SELECT testapp_author.name, testapp_author.email, testapp_author.id '.
            'FROM testapp_author  '.
            'INNER JOIN testapp_book_author ON ( testapp_author.id = testapp_book_author.author_id) '.
            'WHERE testapp_book_author.book_id = '.
            '( SELECT testapp_book.id FROM testapp_book  WHERE testapp_book.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlReverseFkFiltering()
    {
        // *************** scalar
        $query = new Query(new User());
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT testapp_user.first_name, testapp_user.last_name, testapp_user.password, '.
            'testapp_user.email, testapp_user.phone, testapp_user.id '.
            'FROM testapp_user  INNER JOIN testapp_order ON ( testapp_user.id = testapp_order.buyer_id) '.
            'WHERE testapp_order.id = ?';
        $this->assertQuery($query, [$expected, [1]]);
        // *************** Queryset
        $query = new Query(new User());
        $query->addQ(q_(['order' => Order::objects()->filter(['id' => 1])]));

        $expected = 'SELECT testapp_user.first_name, testapp_user.last_name, testapp_user.password, '.
            'testapp_user.email, testapp_user.phone, testapp_user.id '.
            'FROM testapp_user  INNER JOIN testapp_order ON ( testapp_user.id = testapp_order.buyer_id) '.
            'WHERE testapp_order.id = '.
            '( SELECT testapp_order.id FROM testapp_order  WHERE testapp_order.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);
    }
}
