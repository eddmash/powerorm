<?php
/**
 * Orm Loader
 */

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * PowerORM version
 * @ignore
 */
define('POWERORM_VERSION', '1.0.0');
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
 * @package powerorm
 * @since 1.0.0
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

        // statements
        require_once("db/_loader_.php");

        // migrations
        require_once("migrations/_loader_.php");

        // model
        require_once("model/_loader_.php");

        // forms
        require_once("form/_loader_.php");

        // load some utils
        include_once("tools.php");
    }
}



