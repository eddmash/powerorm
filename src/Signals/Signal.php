<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Signals;

use Eddmash\PowerOrm\BaseOrm;

class Signal
{
    const MODEL_PRE_INIT = 'powerorm.model.pre_init';

    public static function dispatch($name, $sender, $kwargs = [])
    {
        if(self::getSignalManager()):
            self::getSignalManager()->dispatch($name, $sender, $kwargs);
        endif;
    }

    /**
     * @return SignalManagerInterface
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function getSignalManager()
    {
        $manager = BaseOrm::getInstance()->getSignalManager();

        if($manager):
            $manager = new SignalManager();
        endif;
        return $manager;
    }
}
