<?php

/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/5/16
 * Time: 11:34 PM
 */
class ObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testOneEqualsOne()
    {
        $this->assertEquals(
            1,
            1,
            'one should be equal to one'
        );
    }
}
