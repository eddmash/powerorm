<?php
use eddmash\powerorm\Object;

/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/5/16
 * Time: 11:34 PM
 */
class ObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testFullClassName()
    {
        $expectedName = 'eddmash\powerorm\Object';
        $obj = new Object();
        $returnedName = $obj->full_class_name();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('full_class_name() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testShortClassName()
    {
        $expectedName = 'Object';
        $obj = new Object();
        $returnedName = $obj->get_class_name();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('get_class_name() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testHasMethod()
    {

        $obj = new Object();
        $this->assertTrue($obj->has_method('get_class_name'), 'Expected True, since the method exists');
    }

    public function testHasProperty()
    {
        $obj = new Object();
        $this->assertTrue($obj->has_property('_signal'), 'Expected return true, since the property exists');
    }

    /**
     * @param $expectedName
     * @param $returnedName
     * @dataProvider providerTestStandardizedName
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testConversionToStandardName($originalName, $expectedName)
    {

        $obj = new Object();
        $returnedName = $obj->standard_name($originalName);

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('standard_name() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function providerTestStandardizedName()
    {
        return array(
            array('slUggified', 'sluggified'),
            array('SLUGGIFIED', 'sluggified'),
            array('sLUggified10', 'sluggified10'),
            array('', ''),
        );
    }



}
