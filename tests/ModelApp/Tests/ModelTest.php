<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\ModelApp\Tests;

use Eddmash\PowerOrm\Exception\FieldError;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Model\Field\OneToOneField;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\ModelApp\ModelApp;
use Eddmash\PowerOrm\Tests\ModelApp\Models\AbstractModel;
use Eddmash\PowerOrm\Tests\ModelApp\Models\ChildConcreteWithImmediateParentProxy;
use Eddmash\PowerOrm\Tests\ModelApp\Models\ConcreteModel;
use Eddmash\PowerOrm\Tests\ModelApp\Models\DirectConcreateBaseProxy;
use Eddmash\PowerOrm\Tests\ModelApp\Models\InDirectConcreateBaseProxy;
use Eddmash\PowerOrm\Tests\ModelApp\Models\InnerAbstractClass;
use Eddmash\PowerOrm\Tests\ModelApp\TestModels\AbstractBaseProxyModel;
use Eddmash\PowerOrm\Tests\ModelApp\TestModels\AbstractWithFieldsBaseProxyModel;
use Eddmash\PowerOrm\Tests\ModelApp\TestModels\FieldClashModel;
use ReflectionObject;

class ModelTest extends AppAwareTest
{
    public function testClassParents()
    {
        $expected = [
            'Eddmash\PowerOrm\Model\Model',
            'Eddmash\PowerOrm\DeconstructableObject',
            'Eddmash\PowerOrm\BaseObject',
        ];
        $mock = $this->getMockForAbstractClass(Model::class,
            [['registry' => $this->registry]]);

        $this->assertEquals(
            $expected,
            array_keys(ClassHelper::getParents($mock)),
            'Failed to assert expected order of parents'
        );
    }

    public function testAppName()
    {
        $model = new ConcreteModel(['registry' => $this->registry]);
        $this->modelSetup($model);
        $this->assertEquals(ModelApp::AppName,
            $model->getMeta()->getAppName());
    }

    public function testFieldClashInChildModel()
    {
        $this->expectException(FieldError::class);
        $proxy = new FieldClashModel(['registry' => $this->registry]);
        $this->modelSetup($proxy);
    }

    public function testNoFieldsInParentAbstracClassForProxyClass()
    {
        $this->expectException(TypeError::class);
        $proxy = new AbstractWithFieldsBaseProxyModel(['registry' => $this->registry]);
        $this->modelSetup($proxy);
    }

    public function testProxyHasConcreteBase()
    {
        $this->expectException(TypeError::class);
        $proxy = new AbstractBaseProxyModel(['registry' => $this->registry]);
        $this->modelSetup($proxy);
    }

    public function testConcreteHierachyMeta()
    {
        $proxy = new ConcreteModel(['registry' => $this->registry]);
        $this->modelSetup($proxy);
        /** @var $immediateParent ReflectionObject */
        list(
            $concreteParentName, $immediateParent,
            $classFields, $parentLink
            ) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(null, $concreteParentName);
        $this->assertEquals(AbstractModel::class, $immediateParent->getName());
        $this->assertEquals(true, $immediateParent->isAbstract());
        $this->assertEquals(null, $parentLink);

        $fields = [
            'country',
            'school',
            'town',
        ];
        $this->assertEquals($fields, array_keys($classFields));
    }

    public function testParentLinkHierachy()
    {
        $proxy = new ChildConcreteWithImmediateParentProxy(['registry' => $this->registry]);
        $this->modelSetup($proxy);
        /** @var $immediateParent ReflectionObject */
        list(
            $concreteParentName, $immediateParent,
            $classFields, $parentLink
            ) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(DirectConcreateBaseProxy::class, $immediateParent->getName());
        $this->assertEquals(false, $immediateParent->isAbstract());
        $this->assertEquals(OneToOneField::class, get_class($parentLink));

        $fields = [
            'child',
            'concretemodel_ptr',
        ];
        $this->assertEquals($fields, array_keys($classFields));
    }

    public function testProxyHasDirectConcreteBaseHierachyMeta()
    {
        /** @var $immediateParent ReflectionObject */
        $proxy = new DirectConcreateBaseProxy(['registry' => $this->registry]);
        $this->modelSetup($proxy);
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(ConcreteModel::class, $immediateParent->getName());
        $this->assertEquals(false, $immediateParent->isAbstract());
    }

    public function testProxyHasInDirectConcreteBaseHierachyMeta()
    {
        /** @var $immediateParent ReflectionObject */
        $proxy = new InDirectConcreateBaseProxy(['registry' => $this->registry]);
        $this->modelSetup($proxy);
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(InnerAbstractClass::class, $immediateParent->getName());
        $this->assertEquals(true, $immediateParent->isAbstract());
    }

    private function modelSetup(Model $proxy)
    {
        $proxy->setupClassInfo(
            [],
            [
                'meta' => ['appName' => ModelApp::AppName],
                'registry' => $this->registry,
            ]
        );

        return $proxy;
    }

    protected function getComponents(): array
    {
        return [ModelApp::class];
    }
}
