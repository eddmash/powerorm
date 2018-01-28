<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\Model\Field\OneToOneField;
use Eddmash\PowerOrm\Model\Model;

const TESTAPPNAME = 'test';
class ModelTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Eddmash\PowerOrm\BaseOrm::setup(new Settings([]));
    }

    public function testAppName()
    {
        $proxy = new ConcreteModel();
        $this->modelSetup($proxy);
        $this->assertEquals(TESTAPPNAME, $proxy->getMeta()->getAppName());
    }

    public function testFieldClashInChildModel()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\FieldError::class);
        $proxy = new FieldClashModel();
        $this->modelSetup($proxy);
    }

    public function testNoFieldsInParentAbstracClassForProxyClass()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\TypeError::class);
        $proxy = new AbstractWithFieldsBaseProxyModel();
        $this->modelSetup($proxy);
    }

    public function testProxyHasConcreteBase()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\TypeError::class);
        $proxy = new AbstractBaseProxyModel();
        $this->modelSetup($proxy);
    }

    public function testConcreteHierachyMeta()
    {
        $proxy = new ConcreteModel();
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
        $proxy = new ChildConcreteWithImmediateParentProxy();
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
        $proxy = new DirectConcreateBaseProxy();
        $this->modelSetup($proxy);
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(ConcreteModel::class, $immediateParent->getName());
        $this->assertEquals(false, $immediateParent->isAbstract());
    }

    public function testProxyHasInDirectConcreteBaseHierachyMeta()
    {
        /** @var $immediateParent ReflectionObject */
        $proxy = new InDirectConcreateBaseProxy();
        $this->modelSetup($proxy);
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(InnerAbstractClass::class, $immediateParent->getName());
        $this->assertEquals(true, $immediateParent->isAbstract());
    }

    private function modelSetup(Model $proxy)
    {
        $proxy->setupClassInfo([], ['meta' => ['appName' => TESTAPPNAME]]);

        return $proxy;
    }
}

abstract class AbstractModel extends \Eddmash\PowerOrm\Model\Model
{
    public function unboundFields()
    {
        return [
            'country' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
            'school' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

class FieldClashModel extends AbstractModel
{
    public function unboundFields()
    {
        return [
            'school' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

class AbstractWithFieldsBaseProxyModel extends AbstractModel
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}

class AbstractBaseProxyModel extends Model
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}

class ConcreteModel extends AbstractModel
{
    public function unboundFields()
    {
        return [
            'town' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

class DirectConcreateBaseProxy extends ConcreteModel
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}

class ChildConcreteWithImmediateParentProxy extends DirectConcreateBaseProxy
{
    public function unboundFields()
    {
        return [
            'child' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

abstract class InnerAbstractClass extends ConcreteModel
{
}

class InDirectConcreateBaseProxy extends InnerAbstractClass
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}
