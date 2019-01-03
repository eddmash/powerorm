<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 11/3/18
 * Time: 4:13 AM.
 */

namespace Eddmash\PowerOrm\Tests;

use Eddmash\PowerOrm\Tests\TestApp\Models\Product;

class SingleTest extends PowerormTest
{
    public function testPrefetch()
    {
        Product::objects()->prefetchRelated()->getResults();
    }
}
