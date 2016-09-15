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
     * This should return all the information (as an array) required to recreate this object again.
     *
     * i.e.
     *
     * - the class name
     * - the absolute path to the class as a string
     * - the constructor arguments as an array
     *
     * e.g.
     *
     *  <pre><code> $name = Orm::Charfield(['max_length'=>20]);
     *
     * var_dump($name->skeleton());
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
    public function skeleton();

    /**
     * Should return an array of all the arguments that the constructor takes in.
     *
     * @return array
     */
    public function constructorArgs();
}
