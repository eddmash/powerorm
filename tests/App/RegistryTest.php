<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 11:50 PM.
 */

namespace Eddmash\PowerOrm\Tests\App;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Tests\PowerormTest;
use Eddmash\PowerOrm\Tests\TestApp\Models\Author;

class RegistryTest extends PowerormTest
{
    public function testFilesInModelFolder()
    {
        $files = BaseOrm::getRegistry()->getModelFiles();
        $name = array_keys($files)[0];
        $this->assertEquals(
            'testapp',
            $name
        );
        $this->assertEquals(
            [
                BASEPATH . '/tests/TestApp/Models/Author.php',
                BASEPATH . '/tests/TestApp/Models/Book.php',
                BASEPATH . '/tests/TestApp/Models/NotAModel.php',
            ],
            $files[$name]
        );
    }

    public function testAllModelsWithoutAutoCreated()
    {
        $classes = BaseOrm::getRegistry()->getModels(
            false,
            'testapp'
        );
        $this->assertEquals(
            [
                "Eddmash\PowerOrm\Tests\TestApp\Models\Author",
                "Eddmash\PowerOrm\Tests\TestApp\Models\Book",
            ],
            array_keys($classes)
        );
    }

    public function testAllModelsWithAutoCreated()
    {
        $classes = BaseOrm::getRegistry()->getModels(
            true,
            'testapp'
        );
        $this->assertEquals(
            [
                "Eddmash\PowerOrm\Tests\TestApp\Models\Author",
                'Book_author',
                "Eddmash\PowerOrm\Tests\TestApp\Models\Book",
            ],
            array_keys($classes)
        );
    }

    public function testGetModel()
    {
        $ref = new \ReflectionClass(Author::class);
        $model = BaseOrm::getRegistry()->getModel($ref->getName());

        // with namespace
        $this->assertEquals($ref->getName(), get_class($model));

        // with namespace insensitive
        $this->assertEquals(
            strtolower($ref->getName()),
            strtolower(get_class($model))
        );

        // with no namespace
        $this->expectException(LookupError::class);
        BaseOrm::getRegistry()->getModel($ref->getShortName());

        // no existent class
        $this->expectException(LookupError::class);
        BaseOrm::getRegistry()->getModel('x');
    }
}
