<?php
/**
 * Orm Loader
 */


/**
 * class that Loads the ORM LIBRARY
 *
 * To start using the orm, load it like any other CODIGNTER library, preferably using autoload
 * <pre><code>$autoload['libraries'] = array(
 *          'powerdispatch/signal',
 *          'powerorm/orm', <------------------------------------ the orm
 *          'powerauth/auth'
 * );</code></pre>
 *
 *
 * @package POWERCI
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

class Orm{

    /**
     * @ignore
     */
    public function __construct(){
        $this->init();

    }

    /**
     * initializes the orm
     * @internal
     */
    private function init(){

        // load the CI model class
        include_once(BASEPATH."core/Model.php");

        // load queryset
        include_once("Queryset.php");

        // load base model into the class scope
        include_once("Base_model.php");

        // load exceptions
        include_once("OrmExceptions.php");
    }
}



