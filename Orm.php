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

        // load some utility tools aka helpers
        include_once("tools.php");

        // exceptions
        require_once("exceptions/__init__.php");

        // statements
        require_once("db/__init__.php");

        // migrations
        require_once("migrations/__init__.php");

        // Queries
        require_once("queries/__init__.php");

        // model
        require_once("model/__init__.php");

        // forms
        require_once("form/__init__.php");

    }
}



