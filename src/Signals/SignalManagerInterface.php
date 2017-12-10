<?php

namespace Eddmash\PowerOrm\Signals;

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface SignalManagerInterface
{
    function dispatch($signal, $sender, $kwargs = []);
}