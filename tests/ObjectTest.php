<?php

use Eddmash\PowerOrm\Object;

/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/5/16
 * Time: 11:34 PM.
 */
class ObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testFullClassName()
    {
        $expectedName = 'Eddmash\PowerOrm\Object';
        $obj = new Object();
        $returnedName = $obj->getFullClassName();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('getFullClassName() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testShortClassName()
    {
        $expectedName = 'Object';
        $obj = new Object();
        $returnedName = $obj->getShortClassName();

        $this->assertEquals(
            $expectedName,
            $returnedName,
            sprintf('getShortClassName() returned %s but we expected %s', $returnedName, $expectedName)
        );
    }

    public function testHasMethod()
    {
        $obj = new Object();
        $this->assertTrue($obj->hasMethod('getFullClassName'), 'Expected True, since the method exists');
    }

    public function testHasProperty()
    {
        $obj = new Object();
        $this->assertTrue($obj->hasProperty('_signal'), 'Expected return true, since the property exists');
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
        $obj = new Object();
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
