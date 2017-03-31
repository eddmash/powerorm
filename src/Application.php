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

use Composer\Autoload\ClassLoader;
use Eddmash\PowerOrm\Console\Manager;
use Eddmash\PowerOrm\Helpers\ClassHelper;

define('POWERORM_VERSION', '1.1.0-pre-alpha');

/**
 * Class Application.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Application
{
    /**
     * @param array $config
     * @param $composerLoader
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function webRun($config = [], &$composerLoader = null)
    {
        return static::run($config, $composerLoader);
    }

    /**
     * @param $config
     * @param ClassLoader $composerLoader
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function run($config, &$composerLoader)
    {
        $orm = BaseOrm::createObject($config);
        $modelsNamespace = $orm->modelsNamespace;
        $migrationsNamespace = $orm->migrationNamespace;

        if ($modelsNamespace):
            $modelsNamespace = ClassHelper::getFormatNamespace($orm->modelsNamespace, false, true);
        endif;

        if ($migrationsNamespace) :
            $migrationsNamespace = ClassHelper::getFormatNamespace($orm->migrationNamespace, false, true);
        endif;

        if ($composerLoader) :
            $composerLoader->setPsr4($modelsNamespace, $orm->modelsPath);
            $composerLoader->setPsr4($migrationsNamespace, $orm->migrationPath);
        endif;

        BaseOrm::loadRegistry($orm);

        return $orm;
    }

    /**
     * @param ClassLoader $composerLoader
     * @param array       $config
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @return \Symfony\Component\Console\Application
     */
    public static function consoleRun($config, &$composerLoader = null, $autoRun = true)
    {

        static::run($config, $composerLoader);

        return Manager::run($autoRun);
    }

}
