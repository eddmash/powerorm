<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\RegistryApp\Tests;

use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\RegistryApp\Models\Author;
use Eddmash\PowerOrm\Tests\RegistryApp\RegistryApp;

class RegistryTest extends AppAwareTest
{
    private $modelspath;

    /**
     * @var AppInterface
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    private $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = $this->orm->getComponent(RegistryApp::class);
        $this->modelspath = $this->component->getModelsPath();
    }

    public function testFilesInModelFolder()
    {
        $files = $this->orm->getRegistryCache()->getModelFiles();

        $name = array_keys($files)[0];
        $this->assertEquals(
            RegistryApp::class,
            $name
        );
        $expected = [
            $this->modelspath.'/Author.php',
            $this->modelspath.'/Book.php',
            $this->modelspath.'/NotAModel.php',
            $this->modelspath.'/Order.php',
            $this->modelspath.'/OrderItem.php',
            $this->modelspath.'/Product.php',
            $this->modelspath.'/User.php',
        ];
        $actual = $files[$name];
        sort($actual);
        sort($expected);
        $this->assertCount(7, $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testAllModelsWithoutAutoCreatedExcluded()
    {
        $classes = $this->orm->getRegistryCache()->getModels(
            false,
            RegistryApp::class
        );
        $expected = [
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Author",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Book",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Order",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\OrderItem",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Product",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\User",
        ];

        $actual = array_keys($classes);

        sort($expected);
        sort($actual);

        $this->assertCount(6, $actual);

        $this->assertEquals($expected, $actual);
    }

    public function testAllModelsWithAutoCreatedIncluded()
    {
        $classes = $this->orm->getRegistryCache()->getModels(
            true,
            RegistryApp::class
        );

        $expected = [
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Author",
            'Eddmash\PowerOrm\Tests\RegistryApp\Models\Book_author_autogen',
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Book",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Order",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\OrderItem",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\Product",
            "Eddmash\PowerOrm\Tests\RegistryApp\Models\User",
        ];

        $actual = array_keys($classes);
        sort($expected);
        sort($actual);

        $this->assertCount(7, $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetModel()
    {
        $ref = new \ReflectionClass(Author::class);
        $model = $this->orm->getRegistryCache()->getModel($ref->getName());

        // with namespace
        $this->assertEquals($ref->getName(), get_class($model));

        // with namespace insensitive
        $this->assertEquals(
            strtolower($ref->getName()),
            strtolower(get_class($model))
        );

        // with no namespace
        $this->expectException(LookupError::class);
        $this->orm->getRegistryCache()->getModel($ref->getShortName());

        // no existent class
        $this->expectException(LookupError::class);
        $this->orm->getRegistryCache()->getModel('x');
    }

    protected function getComponents(): array
    {
        return [RegistryApp::class];
    }
}
