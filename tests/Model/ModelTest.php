<?php

/**
 * This file is part of the ci306 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Eddmash\PowerOrm\Model\Model;

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Eddmash\PowerOrm\BaseOrm::loadRegistry();
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
        list($concreteParentName, $immediateParent, $parentIsAbstract, $classFields) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(AbstractModel::class, $immediateParent);
        $this->assertEquals(true, $parentIsAbstract);

        $fields = [
            'country',
            'school',
            'town',
        ];
        $this->assertEquals($fields, array_keys($classFields));
    }

    public function testProxyHasDirectConcreteBaseHierachyMeta()
    {
        $proxy = new DirectConcreateBaseProxy();
        list($concreteParentName, $immediateParent, $parentIsAbstract) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(ConcreteModel::class, $immediateParent);
        $this->assertEquals(false, $parentIsAbstract);
    }

    public function testProxyHasInDirectConcreteBaseHierachyMeta()
    {
        $proxy = new InDirectConcreateBaseProxy();
        list($concreteParentName, $immediateParent, $parentIsAbstract) = Model::getHierarchyMeta($proxy);

        $this->assertEquals(ConcreteModel::class, $concreteParentName);
        $this->assertEquals(InnerAbstractClass::class, $immediateParent);
        $this->assertEquals(true, $parentIsAbstract);
    }
}

abstract class AbstractModel extends \Eddmash\PowerOrm\Model\Model
{
    private function unboundFields()
    {
        return [
            'country' => PModel::CharField(['maxLength' => 40, 'dbIndex' => true]),
            'school' => PModel::CharField(['maxLength' => 40, 'dbIndex' => true]),
        ];
    }
}

class FieldCrashModel extends AbstractModel
{
    private function unboundFields()
    {
        return [
            'school' => PModel::CharField(['maxLength' => 40, 'dbIndex' => true]),
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
            'town' => PModel::CharField(['maxLength' => 40, 'dbIndex' => true]),
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
