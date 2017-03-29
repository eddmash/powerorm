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

use Eddmash\PowerOrm\Console\Manager;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Symfony\Component\Debug\ErrorHandler;

/**
 * Class Application.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Application
{
    public static function webRun($config = [])
    {
        static::run($config);
    }

    public static function run($config)
    {
        $baseDir = ArrayHelper::pop($config, 'baseDir');

//        if (strtolower(basename($baseDir)) === 'powerorm'):
//            define('ENVIRONMENT', 'POWERORM_DEV');
//        endif;

//        // bootstrap the orm.
        require_once 'bootstrap.php';

        // load doctrine DBAL
        self::loadThirdParty();
    }

    public static function consoleRun($config = [])
    {
        static::run($config);
        $orm = BaseOrm::setup($config);
        $orm->registerModelChecks();
        Manager::run();
    }

    /**
     * Loads third party libraries.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function loadThirdParty()
    {
        $ds = DIRECTORY_SEPARATOR;

//        $vendorDir = sprintf('%1$s%2$svendor%2$s', HOMEPATH, $ds);

//        if (file_exists($vendorDir.'doctrine')):
//
//            $path = '%1$sdoctrine%2$scommon%2$slib%2$sDoctrine%2$sCommon%2$sClassLoader.php';
//
//            require sprintf($path, $vendorDir, $ds);

//            $commonLoader = new \Doctrine\Common\ClassLoader(
//                'Doctrine',
//                $vendorDir.'doctrine'.$ds.'common'.$ds.'lib'
//            );
//
//            $commonLoader->register();
//
//            $dbalLoader = new \Doctrine\Common\ClassLoader(
//                'Doctrine',
//                $vendorDir.'doctrine'.$ds.'dbal'.$ds.'lib'
//            );
//
//            $dbalLoader->register();
//
////        endif;
//
//        if (file_exists($vendorDir.$ds.'symfony'.$ds.'debug')):
//            ErrorHandler::register();
//        endif;
    }
}
