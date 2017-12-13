<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\Helpers\ClassHelper;

class ClassHelperTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Eddmash\PowerOrm\BaseOrm::setup(new Settings([]));
    }

    /**
     * @dataProvider namespaceProvider
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testNamespanceNamePair($originalValue, $expectedNamespace, $expectedName)
    {
        list($namespace, $name) = ClassHelper::getNamespaceNamePair($originalValue);
        $this->assertEquals($expectedNamespace, $namespace);
        $this->assertEquals($expectedName, $name);
    }

    public function testGettingClassNameFromFile()
    {
        $classDir = '/var/www/public/ci306/application/migrations';
        $file = $classDir.'/m0001_Initial.php';

        $this->assertEquals('m0001_Initial', ClassHelper::getClassNameFromFile($file, $classDir));
    }

    public function testClassParents()
    {
        $expected = [
            'Eddmash\PowerOrm\Model\Model',
            'Eddmash\PowerOrm\DeconstructableObject',
            'Eddmash\PowerOrm\BaseObject',
        ];
        $mock = $this->getMockForAbstractClass(\Eddmash\PowerOrm\Model\Model::class);

        $this->assertEquals($expected, array_keys(ClassHelper::getParents($mock)), 'Failed to assert expected order of parents');
    }

    /**
     * @dataProvider namespaceBothBackslashProvider
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testFormatingNamespaceWithOpenAndCloseBackslash($originalValue, $expectedValue)
    {
        $this->assertEquals($expectedValue, ClassHelper::getFormatNamespace($originalValue, true));
    }

    /**
     * @dataProvider namespaceLeadingBackslashProvider
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testFormatingNamespaceWithLeadingBackslash($originalValue, $expectedValue)
    {
        $this->assertEquals($expectedValue, ClassHelper::getFormatNamespace($originalValue, true, false));
    }

    /**
     * @dataProvider namespaceClosingBackslashProvider
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testFormatingNamespaceWithClosingBackslash($originalValue, $expectedValue)
    {
        $this->assertEquals($expectedValue, ClassHelper::getFormatNamespace($originalValue, false, true));
    }

    /**
     * @dataProvider nameFromNamespaceProvider
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testGettingNameFromNamespace($originalValue, $expectedValue)
    {
        $this->assertEquals($expectedValue, ClassHelper::getNameFromNs($originalValue, '\app\model'));
    }

    public function namespaceProvider()
    {
        return [
            ['app\model\farmers\Farmer', 'app\model\farmers', 'Farmer'],
            ['app\model\User\\', 'app\model', 'User'],
        ];
    }

    public function nameFromNamespaceProvider()
    {
        return [
            ['app\model\farmers\Farmer', 'farmers\Farmer'],
            ['app\model\User\\', 'User'],
        ];
    }

    public function namespaceBothBackslashProvider()
    {
        return [
            ['app\model\farmers\Farmer', '\app\model\farmers\Farmer\\'],
            ['app\model\User\\', '\app\model\User\\'],
            ['\app\model\Permission\\', '\app\model\Permission\\'],
            ['\app\model\Role', '\app\model\Role\\'],
        ];
    }

    public function namespaceLeadingBackslashProvider()
    {
        return [
            ['app\model\farmers\Farmer', '\app\model\farmers\Farmer'],
            ['app\model\User\\', '\app\model\User'],
            ['\app\model\Permission\\', '\app\model\Permission'],
            ['\app\model\Role', '\app\model\Role'],
        ];
    }

    public function namespaceClosingBackslashProvider()
    {
        return [
            ['app\model\farmers\Farmer\\', 'app\model\farmers\Farmer\\'],
            ['app\model\User\\', 'app\model\User\\'],
            ['\app\model\Permission\\', 'app\model\Permission\\'],
            ['\app\model\Role', 'app\model\Role\\'],
        ];
    }
}
