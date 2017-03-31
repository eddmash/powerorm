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
    public static function webRun($config = [])
    {
        static::run($config);
    }

    public static function run($config)
    {
        return BaseOrm::createObject($config);
    }

    /**
     * @param ClassLoader $composerLoader
     * @param array       $config
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function consoleRun($composerLoader, $config = [])
    {
        $orm = static::run($config);

        $modelsNamespace = ClassHelper::getFormatNamespace($orm->modelsNamespace, false, true);
        $migrationsNamespace = ClassHelper::getFormatNamespace($orm->migrationNamespace, false, true);
        $composerLoader->setPsr4($modelsNamespace, $orm->modelsPath);
        $composerLoader->setPsr4($migrationsNamespace, $orm->migrationPath);

        BaseOrm::loadRegistry($orm);

        Manager::run();
    }

}
