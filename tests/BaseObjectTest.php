<?php

use Eddmash\PowerOrm\BaseObject;

/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/5/16.
 */
class BaseObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var object
     */
    public $instance;

    public function setup()
    {
        $this->instance = new BaseObject();
    }

    public function teardown()
    {
        unset($this->instance);
    }

    public function testFullClassName()
    {
        $expectedName = 'Eddmash\PowerOrm\BaseObject';
        $returnedName = $this->instance->getFullClassName();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('getFullClassName() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testShortClassName()
    {
        $expectedName = 'BaseObject';

        $returnedName = $this->instance->getShortClassName();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('getShortClassName() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testHasMethod()
    {
        $this->assertTrue($this->instance->hasMethod('getFullClassName'), 'Expected True, since the method exists');
    }

    public function testHasProperty()
    {
        $this->assertTrue($this->instance->hasProperty('_signal'), 'Expected return true, since the property exists');
    }

    /**
     * @param $expectedName
     * @param $returnedName
     * @dataProvider providerTestNormalizeKey
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testConversionToNormalizeKey($originalName, $expectedName)
    {
        $obj = new BaseObject();
        $returnedName = $obj->normalizeKey($originalName);

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('normalizeKey() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function providerTestNormalizeKey()
    {
        return array(
            array('slUggified', 'sluggified'),
            array('SLUGGIFIED', 'sluggified'),
            array('sLUggified10', 'sluggified10'),
            array('', ''),
        );
    }
}
