<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

require POWERORM_BASEPATH.'autoloader/BaseConfig.php';

/**
 * Borrowed from CodeIgniter 4.
 * -------------------------------------------------------------------
 * AUTO-LOADER
 * -------------------------------------------------------------------
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 */
class OrmConfig extends BaseConfig
{
    public $psr4 = [];

    public $classmap = [];

    //--------------------------------------------------------------------

    /**
     * Collects the application-specific autoload settings and merges
     * them with the framework's required settings.
     *
     * NOTE: If you use an identical key in $psr4 or $classmap, then
     * the values in this file will overwrite the framework's values.
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * -------------------------------------------------------------------
         * Namespaces
         * -------------------------------------------------------------------
         * This maps the locations of any namespaces in your application
         * to their location on the file system. These are used by the
         * Autoloader to locate files the first time they have been instantiated.
         *
         * The '/application' and '/system' directories are already mapped for
         * you. You may change the name of the 'App' namespace if you wish,
         * but this should be done prior to creating any namespaced classes,
         * else you will need to modify all of those classes for this to work.
         *
         * DO NOT change the name of the CodeIgniter namespace or your application
         * WILL break. *
         * Prototype:
         *
         *   $Config['psr4'] = [
         *       'CodeIgniter' => SYSPATH
         *   `];
         */
        $psr4 = [
            'app\migrations'      => APPPATH.'migrations',
            'powerorm'            => realpath(POWERORM_BASEPATH),
        ];

        /*
         * -------------------------------------------------------------------
         * Class Map
         * -------------------------------------------------------------------
         * The class map provides a map of class names and their exact
         * location on the drive. Classes loaded in this manner will have
         * slightly faster performance because they will not have to be
         * searched for within one or more directories as they would if they
         * were being autoloaded through a namespace.
         *
         * Prototype:
         *
         *   $Config['classmap'] = [
         *       'MyClass'   => '/path/to/class/file.php'
         *   ];
         */
        $classmap = [
            'PModel'                                             => POWERORM_BASEPATH.'model/PModel.php',
            'PModelForm'                                         => POWERORM_BASEPATH.'form/PForm.php',
            'PForm'                                              => POWERORM_BASEPATH.'form/PForm.php',

            'powerorm\migrations\ProjectState'                   => POWERORM_BASEPATH.'migrations/State.php',
            'powerorm\migrations\ModelState'                     => POWERORM_BASEPATH.'migrations/State.php',
            'powerorm\migrations\StateApps'                      => POWERORM_BASEPATH.'migrations/State.php',
            'powerorm\migrations\Questioner'                     => POWERORM_BASEPATH.'migrations/Questioner.php',
            'powerorm\migrations\InteractiveQuestioner'          => POWERORM_BASEPATH.'migrations/Questioner.php',

            'CI_Model'                                             => BASEPATH.'/core/Model.php',
            'CI_DB_forge'                                          => BASEPATH.'/database/DB_forge.php',
            'CI_DB_driver'                                         => BASEPATH.'/database/DB_driver.php',
            'CI_DB_query_builder'                                  => BASEPATH.'/database/DB_query_builder.php',
        ];

        //--------------------------------------------------------------------
        // Do Not Edit Below This Line
        //--------------------------------------------------------------------

        $this->psr4 = array_merge($this->psr4, $psr4);
        $this->classmap = array_merge($this->classmap, $classmap);

        unset($psr4, $classmap);
    }

    //--------------------------------------------------------------------
}
