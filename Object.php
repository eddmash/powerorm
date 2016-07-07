<?php
namespace powerorm;


use powerorm\traits\BaseObject;

// **************************** INTERFACES *************************

/**
 * Interface DeConstruct
 * @package powerorm
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface DeConstruct
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
     *      'full_name'=> 'powerorm\model\Charfield',// since the path and the name can contain alias, pass this also
     *      'path'=> 'powerorm\model\Charfield',
     *      'constructor_args'=> [
     *          'max_length'=> 20
     *      ]
     * ]
     *
     *
     * </code></pre>
     * @return array
     */
    public function skeleton();

    /**
     * Should return an array of all the arguments that the constructor takes in.
     * @return array
     */
    public function constructor_args();
}

/**
 * Interface Contributor
 * @package powerorm
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface Contributor{

    /**
     * Add the current object to the passed in object
     * @param string $propery_name the name map the current object to, in the class object passed in.
     * @param Object $class_object the object to attach the current object to.
     * @return mixed
     */
    public function contribute_to_class($propery_name, $class_object);
}

// **************************** CLASSES *************************

/**
 * Class NOT_PROVIDED
 * @package powerorm
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NOT_PROVIDED{}

/**
 * Class Object
 * @package powerorm
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Object
{

    use BaseObject;
}