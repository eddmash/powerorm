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
use Eddmash\PowerOrm\Model\Model;

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Eddmash\PowerOrm\BaseOrm::setup(new Settings([]));
    }

    public function testFieldMethodIsNotPublic()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\MethodNotExtendableException::class);
        new PublicFieldMethodModel();
    }

    public function testFieldCrashInChildModel()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\FieldError::class);
        new FieldCrashModel();
    }

    public function testNoFieldsInParentAbstracClassForProxyClass()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\TypeError::class);
        new AbstractWithFieldsBaseProxyModel();
    }

    public function testProxyHasConcreteBase()
    {
        $this->expectException(\Eddmash\PowerOrm\Exception\TypeError::class);
        new AbstractBaseProxyModel();
    }

    public function testConcreteHierachyMeta()
    {
        $proxy = new ConcreteModel();
        /** @var $immediateParent ReflectionObject */
        list($concreteParentName, $immediateParent, $classFields) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(AbstractModel::class, $immediateParent->getName());
        $this->assertEquals(true, $immediateParent->isAbstract());

        $fields = [
            'country',
            'school',
            'town',
        ];
        $this->assertEquals($fields, array_keys($classFields));
    }

    public function testProxyHasDirectConcreteBaseHierachyMeta()
    {
        /** @var $immediateParent ReflectionObject */
        $proxy = new DirectConcreateBaseProxy();
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(ConcreteModel::class, $immediateParent->getName());
        $this->assertEquals(false, $immediateParent->isAbstract());
    }

    public function testProxyHasInDirectConcreteBaseHierachyMeta()
    {
        /** @var $immediateParent ReflectionObject */
        $proxy = new InDirectConcreateBaseProxy();
        list($concreteParentName, $immediateParent) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(InnerAbstractClass::class, $immediateParent->getName());
        $this->assertEquals(true, $immediateParent->isAbstract());
    }
}

abstract class AbstractModel extends \Eddmash\PowerOrm\Model\Model
{
    private function unboundFields()
    {
        return [
            'country' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
            'school' => Model::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

class FieldCrashModel extends AbstractModel
{
    private function unboundFields()
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

class PublicFieldMethodModel extends Model
{
    public function unboundFields()
    {
        return [
        ];
    }
}

class ConcreteModel extends AbstractModel
{
    private function unboundFields()
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
