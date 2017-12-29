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
use Eddmash\PowerOrm\App\Settings;
use Eddmash\PowerOrm\Console\Manager;
use Eddmash\PowerOrm\Helpers\ClassHelper;

define('POWERORM_VERSION', '1.1.0-alpha');
define('POWERORM_HOME', dirname(dirname(__FILE__)));

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
        return static::setup($config, $composerLoader);
    }

    /**
     * @param $config
     * @param ClassLoader $composerLoader
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function setup($config, &$composerLoader = null)
    {
        $settings = new Settings($config);
        $orm = BaseOrm::setup($settings);
        $modelsNamespace = $settings->getModelsNamespace();
        $migrationsNamespace = $settings->getMigrationNamespace();

        if ($modelsNamespace):
            $modelsNamespace = ClassHelper::getFormatNamespace($modelsNamespace, false, true);
        endif;

        if ($migrationsNamespace) :
            $migrationsNamespace = ClassHelper::getFormatNamespace($migrationsNamespace, false, true);
        endif;

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
        static::setup($config, $composerLoader);

        return Manager::run($autoRun);
    }
}
