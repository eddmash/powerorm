<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Eddmash\PowerOrm\Model\Query\Lookup;

class LoookupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerLookUp
     *
     * @param $value
     * @param $expected
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testLookupGetLookup($value, $expected) {
        $this->assertEquals($expected, Lookup::getLookUP($value));
    }

    /**
     * @dataProvider providerLookupColumn
     *
     * @param $value
     * @param $expected
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testLookupColumn($value, $expected) {
        $this->assertEquals($expected, Lookup::getLookupColumn($value));
    }

    /**
     * @dataProvider providerLookupCombine
     *
     * @param $value
     * @param $expected
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testLookupCombine($value, $expected) {
        $this->assertEquals($expected, Lookup::combine($value));
    }

    public function providerLookup() {

        return [
            ['name__notin', 'notin'],
            ['name__in', 'in'],
            ['name', 'eq'],
            ['name__icontains', 'icontains'],
            ['name__gte', 'gte'],
        ];
    }

    public function providerLookupColumn() {

        return [
            ['name__notin', 'name'],
            ['age__in', 'age'],
            ['gender', 'gender'],
            ['city__icontains', 'city'],
            ['country__gte', 'country'],
        ];
    }
    public function providerLookupCombine() {

        return [
            ['name__notin', ' && '],
            ['~age__in', ' || '],
            ['gender', ' && '],
            ['~city__icontains', ' || '],
            ['country__gte', ' && '],
        ];
    }
}
