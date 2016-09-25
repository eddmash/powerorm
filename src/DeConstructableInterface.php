<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm;

interface DeConstructableInterface
{
    /**
     * This should return all the information (as an array) required to recreate an object again.
     *
     * i.e.
     *
     * - the class name
     * - the absolute path to the class as a string,This should be the most portable version, so less specific may be better.
     * - the constructor arguments as an array
     *
     * e.g.
     *
     *  <pre><code> $name = Orm::Charfield(['max_length'=>20]);
     *
     * var_dump($name->deconstruct());
     *
     * [
     *      'name'=> 'Charfield',
     *      'fullName'=> 'Eddmash\PowerOrm\Model\Charfield',// since the path and the name can contain alias, pass this also
     *      'path'=> 'Eddmash\PowerOrm\Model\Charfield',
     *      'constructorArgs'=> [
     *          'max_length'=> 20
     *      ]
     * ]
     *
     *
     * </code></pre>
     *
     * @return array
     */
    public function deconstruct();

    /**
     * Should return an array of all the arguments that the constructor takes in.
     *
     * @return array
     */
    public function getConstructorArgs();
}
