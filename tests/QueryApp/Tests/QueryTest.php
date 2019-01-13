<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\QueryApp\Tests;

use Eddmash\PowerOrm\Model\Query\Query;
use Eddmash\PowerOrm\Model\Query\Queryset;
use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\QueryApp\Models\Author;
use Eddmash\PowerOrm\Tests\QueryApp\Models\Book;
use Eddmash\PowerOrm\Tests\QueryApp\Models\Order;
use Eddmash\PowerOrm\Tests\QueryApp\Models\OrderItem;
use Eddmash\PowerOrm\Tests\QueryApp\Models\Product;
use Eddmash\PowerOrm\Tests\QueryApp\Models\User;
use Eddmash\PowerOrm\Tests\QueryApp\QueryApp;
use function Eddmash\PowerOrm\Model\Query\Expression\not_;
use function Eddmash\PowerOrm\Model\Query\Expression\q_;

class QueryTest extends AppAwareTest
{
    public function testSqlFetchAll()
    {
        $query = new Query($this->registry->getModel(Author::class));
        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author';

        $this->assertQuery($query, [$expected, []]);
    }

    public function testSqlFetchFiltered()
    {
        $query = new Queryset(null, $this->registry->getModel(Author::class));
        $query = $query->filter(q_(['email' => 'p']));
        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'WHERE queryapp_author.email = ?';

        $this->assertQuery($query, [$expected, ['p']]);
    }

    public function testSqlFetchExclude()
    {
        $query = new Query($this->registry->getModel(Author::class));
        $query->addQ(not_(['email' => 'p']));
        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'WHERE NOT (queryapp_author.email = ?)';

        $this->assertQuery($query, [$expected, ['p']]);
    }

    public function testSqlNotOr()
    {
        $query = new Query($this->registry->getModel(Author::class));
        $query->addQ(not_(['email' => 'p'])->or_(['email' => 'o']));
        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'WHERE (queryapp_author.email = ? OR NOT (queryapp_author.email = ?))';

        $this->assertQuery($query, [$expected, ['o', 'p']]);
    }

    public function testSqlAndOr()
    {
        $query = new Query($this->registry->getModel(Author::class));
        $query->addQ(not_(['email' => 'p'])->and_(['email' => 'o']));
        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'WHERE (queryapp_author.email = ? AND NOT (queryapp_author.email = ?))';

        $this->assertQuery($query, [$expected, ['o', 'p']]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSimpleForeignKey()
    {
        $query = new Query($this->registry->getModel(OrderItem::class));
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT queryapp_orderitem.qty, queryapp_orderitem.price, queryapp_orderitem.id, '.
            'queryapp_orderitem.order_id, queryapp_orderitem.product_id '.
            'FROM queryapp_orderitem  '.
            'WHERE queryapp_orderitem.order_id = ?';

        $this->assertQuery($query, [$expected, [1]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSpanRelationshipFilterByPKField()
    {
        // ********* Scalar value
        $query = new Query($this->registry->getModel(OrderItem::class));
        $query->addQ(q_(['order__buyer' => 1]));

        $expected = 'SELECT queryapp_orderitem.qty, queryapp_orderitem.price, queryapp_orderitem.id, '.
            'queryapp_orderitem.order_id, queryapp_orderitem.product_id '.
            'FROM queryapp_orderitem  '.
            'INNER JOIN queryapp_order ON ( queryapp_orderitem.order_id = queryapp_order.id) '.
            'WHERE queryapp_order.buyer_id = ?';
        $this->assertQuery($query, [$expected, [1]]);

        // *************** Queryset
        $query = new Query($this->registry->getModel(OrderItem::class));
        $query->addQ(q_(['order__buyer' => User::objects($this->registry)->filter(['id' => 30])]));

        $expected = 'SELECT queryapp_orderitem.qty, queryapp_orderitem.price, queryapp_orderitem.id, '.
            'queryapp_orderitem.order_id, queryapp_orderitem.product_id '.
            'FROM queryapp_orderitem  '.
            'INNER JOIN queryapp_order ON ( queryapp_orderitem.order_id = queryapp_order.id) '.
            'WHERE queryapp_order.buyer_id = '.
            '( SELECT queryapp_user.id FROM queryapp_user  WHERE queryapp_user.id = ? )';
        $this->assertQuery($query, [$expected, [30]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlSpanRelationshipFilterByNotPkField()
    {
        $query = new Query($this->registry->getModel(OrderItem::class));
        $query->addQ(q_(['order__buyer__email' => 'a@df.com']));

        $expected = 'SELECT queryapp_orderitem.qty, queryapp_orderitem.price, queryapp_orderitem.id, '.
            'queryapp_orderitem.order_id, queryapp_orderitem.product_id '.
            'FROM queryapp_orderitem  '.
            'INNER JOIN queryapp_order ON ( queryapp_orderitem.order_id = queryapp_order.id) '.
            'INNER JOIN queryapp_user ON ( queryapp_order.buyer_id = queryapp_user.id) '.
            'WHERE queryapp_user.email = ?';

        $this->assertQuery($query, [$expected, ['a@df.com']]);

        // *************** Queryset
        $query = new Query($this->registry->getModel(OrderItem::class));
        $query->addQ(q_(['order__buyer__email' => User::objects($this->registry)->filter(['email' => 'a@df.com'])]));

        $expected = 'SELECT queryapp_orderitem.qty, queryapp_orderitem.price, queryapp_orderitem.id, '.
            'queryapp_orderitem.order_id, queryapp_orderitem.product_id '.
            'FROM queryapp_orderitem  '.
            'INNER JOIN queryapp_order ON ( queryapp_orderitem.order_id = queryapp_order.id) '.
            'INNER JOIN queryapp_user ON ( queryapp_order.buyer_id = queryapp_user.id) '.
            'WHERE queryapp_user.email = '.
            '( SELECT queryapp_user.email FROM queryapp_user  WHERE queryapp_user.email = ? )';
        $this->assertQuery($query, [$expected, ['a@df.com']]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlReverseM2MFiltering()
    {
        // ********* Scalar value reverse via manually defined Through model
        $query = new Query($this->registry->getModel(Product::class));
        $query->addQ(q_(['orders' => 1]));

        $expected = 'SELECT queryapp_product.name, queryapp_product.price, queryapp_product.description, '.
            'queryapp_product.stock, queryapp_product.unit_of_measure, queryapp_product.treshhold, '.
            'queryapp_product.visible, queryapp_product.image, queryapp_product.id, queryapp_product.owner_id, '.
            'queryapp_product.created_by_id '.
            'FROM queryapp_product  '.
            'INNER JOIN queryapp_orderitem ON ( queryapp_product.id = queryapp_orderitem.product_id) '.
            'WHERE queryapp_orderitem.order_id = ?';
        $this->assertQuery($query, [$expected, [1]]);

        // ********* Scalar Queryset reverse via manually defined Through model
        $query = new Query($this->registry->getModel(Product::class));
        $query->addQ(q_(['orders' => Order::objects($this->registry)->filter(['id' => 1])]));

        $expected = 'SELECT queryapp_product.name, queryapp_product.price, queryapp_product.description, '.
            'queryapp_product.stock, queryapp_product.unit_of_measure, queryapp_product.treshhold, '.
            'queryapp_product.visible, queryapp_product.image, queryapp_product.id, queryapp_product.owner_id, '.
            'queryapp_product.created_by_id '.
            'FROM queryapp_product  '.
            'INNER JOIN queryapp_orderitem ON ( queryapp_product.id = queryapp_orderitem.product_id) '.
            'WHERE queryapp_orderitem.order_id = '.
            '( SELECT queryapp_order.id FROM queryapp_order  WHERE queryapp_order.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);

        // ********* Scalar reverse via autocreated Through model
        $query = new Query($this->registry->getModel(Author::class));
        $query->addQ(q_(['book' => 1]));

        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'INNER JOIN queryapp_book_author ON ( queryapp_author.id = queryapp_book_author.author_id) '.
            'WHERE queryapp_book_author.book_id = ?';
        $this->assertQuery($query, [$expected, [1]]);
        // ********* Queryset reverse via autocreated Through model
        $query = new Query($this->registry->getModel(Author::class));
        $query->addQ(q_(['book' => Book::objects($this->registry)->filter(['id' => 1])]));

        $expected = 'SELECT queryapp_author.name, queryapp_author.email, queryapp_author.id '.
            'FROM queryapp_author  '.
            'INNER JOIN queryapp_book_author ON ( queryapp_author.id = queryapp_book_author.author_id) '.
            'WHERE queryapp_book_author.book_id = '.
            '( SELECT queryapp_book.id FROM queryapp_book  WHERE queryapp_book.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\InvalidArgumentException
     */
    public function testSqlReverseFkFiltering()
    {
        // *************** scalar
        $query = new Query($this->registry->getModel(User::class));
        $query->addQ(q_(['order' => 1]));

        $expected = 'SELECT queryapp_user.first_name, queryapp_user.last_name, queryapp_user.password, '.
            'queryapp_user.email, queryapp_user.phone, queryapp_user.id '.
            'FROM queryapp_user  INNER JOIN queryapp_order ON ( queryapp_user.id = queryapp_order.buyer_id) '.
            'WHERE queryapp_order.id = ?';
        $this->assertQuery($query, [$expected, [1]]);
        // *************** Queryset
        $query = new Query($this->registry->getModel(User::class));
        $query->addQ(q_(['order' => Order::objects($this->registry)->filter(['id' => 1])]));

        $expected = 'SELECT queryapp_user.first_name, queryapp_user.last_name, queryapp_user.password, '.
            'queryapp_user.email, queryapp_user.phone, queryapp_user.id '.
            'FROM queryapp_user  INNER JOIN queryapp_order ON ( queryapp_user.id = queryapp_order.buyer_id) '.
            'WHERE queryapp_order.id = '.
            '( SELECT queryapp_order.id FROM queryapp_order  WHERE queryapp_order.id = ? )';
        $this->assertQuery($query, [$expected, [1]]);
    }

    protected function getComponents(): array
    {
        return [QueryApp::class];
    }
}
