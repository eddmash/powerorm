<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Eddmash\PowerOrm\Helpers\ArrayHelper;

class ArrayHelperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerGetArraysValue
     *
     * @param $array
     * @param $key
     * @param $expectedValue
     * @param $default
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testGetValue($array, $key, $expectedValue, $default)
    {
        $value = ArrayHelper::getValue($array, $key, $default);
        $this->assertEquals($expectedValue, $value);
    }

    /**
     * @dataProvider providerHasKey
     *
     * @param $array
     * @param $key
     * @param $expectedValue
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testArrayHasKey($array, $key, $expectedValue)
    {
        $this->assertEquals($expectedValue, ArrayHelper::hasKey($array, $key));
    }

    public function providerHasKey()
    {
        return [
            [['a', 'b', 'c'], 0, true], // check non-assoc
            [['name' => 'mash', 'gender' => 'male'], 'name', true], // check assoc
            [['country' => 'kenya'], 'president', false], // check default
            [['q', 'r', 'tr'], 4, false], // check default
        ];
    }

    public function providerGetArraysValue()
    {
        return [
            [['a', 'b', 'c'], 0, 'a', ''], // check non-assoc
            [['name' => 'mash', 'gender' => 'male'], 'name', 'mash', ''], // check assoc
            [['country' => 'kenya'], 'president', 'not_set', 'not_set'], // check default
        ];
    }
}
