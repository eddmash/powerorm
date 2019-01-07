<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
