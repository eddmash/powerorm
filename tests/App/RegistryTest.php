<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\App;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Tests\PowerormTest;
use Eddmash\PowerOrm\Tests\TestApp\Models\Author;

class RegistryTest extends PowerormTest
{
//    public function testFilesInModelFolder()
//    {
//        $files = BaseOrm::getRegistry()->getModelFiles();
//
//        $name = array_keys($files)[0];
//        $this->assertEquals(
//            'testapp',
//            $name
//        );
//        $expected = [
//            BASEPATH.'/tests/TestApp/Models/Author.php',
//            BASEPATH.'/tests/TestApp/Models/Book.php',
//            BASEPATH.'/tests/TestApp/Models/NotAModel.php',
//            BASEPATH.'/tests/TestApp/Models/Order.php',
//            BASEPATH.'/tests/TestApp/Models/OrderItem.php',
//            BASEPATH.'/tests/TestApp/Models/Product.php',
//            BASEPATH.'/tests/TestApp/Models/User.php',
//            BASEPATH.'/tests/TestApp/Models/MetaTestProfile.php',
//            BASEPATH.'/tests/TestApp/Models/MetaTestUser.php',
//        ];
//        $actual = $files[$name];
//        sort($actual);
//        sort($expected);
//        $this->assertCount(9, $actual);
//        $this->assertEquals($expected, $actual);
//    }
//
//    public function testAllModelsWithoutAutoCreatedExcluded()
//    {
//        $classes = BaseOrm::getRegistry()->getModels(
//            false,
//            'testapp'
//        );
//        $expected = [
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Author",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Book",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Order",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\OrderItem",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Product",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\User",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\MetaTestProfile",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\MetaTestUser",
//        ];
//
//        $actual = array_keys($classes);
//
//        sort($expected);
//        sort($actual);
//
//        $this->assertCount(8, $actual);
//
//        $this->assertEquals($expected, $actual);
//    }
//
//    public function testAllModelsWithAutoCreatedIncluded()
//    {
//        $classes = BaseOrm::getRegistry()->getModels(
//            true,
//            'testapp'
//        );
//
//        $expected = [
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Author",
//            'Eddmash\PowerOrm\Tests\TestApp\Models\Book_author_autogen',
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Book",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Order",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\OrderItem",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\Product",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\User",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\MetaTestProfile",
//            "Eddmash\PowerOrm\Tests\TestApp\Models\MetaTestUser",
//        ];
//
//        $actual = array_keys($classes);
//        sort($expected);
//        sort($actual);
//
//        $this->assertCount(9, $actual);
//        $this->assertEquals($expected, $actual);
//    }
//
//    public function testGetModel()
//    {
//        $ref = new \ReflectionClass(Author::class);
//        $model = BaseOrm::getRegistry()->getModel($ref->getName());
//
//        // with namespace
//        $this->assertEquals($ref->getName(), get_class($model));
//
//        // with namespace insensitive
//        $this->assertEquals(
//            strtolower($ref->getName()),
//            strtolower(get_class($model))
//        );
//
//        // with no namespace
//        $this->expectException(LookupError::class);
//        BaseOrm::getRegistry()->getModel($ref->getShortName());
//
//        // no existent class
//        $this->expectException(LookupError::class);
//        BaseOrm::getRegistry()->getModel('x');
//    }
}
