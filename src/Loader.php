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

define('POWERORM_VERSION', '1.1.0-beta1');
define('POWERORM_HOME', dirname(dirname(__FILE__)));

/**
 * Class Loader.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Loader
{
    /**
     * @param array $config
     * @param       $composerLoader
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function webRun($config = [], &$composerLoader = null)
    {
        return static::setup($config, $composerLoader);
    }

    /**
     * @param             $config
     * @param ClassLoader $composerLoader
     *
     * @return BaseOrm
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     * @throws Exception\KeyError
     */
    public static function setup($config)
    {
        $settings = new Settings($config);
        $orm = BaseOrm::setup($settings);

        return $orm;
    }

    /**
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @return \Symfony\Component\Console\Application
     * @throws \Exception
     */
    public static function consoleRun($config)
    {
        static::setup($config);

        return Manager::run();
    }
}
