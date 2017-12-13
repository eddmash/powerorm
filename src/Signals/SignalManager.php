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

use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalManager implements SignalManagerInterface
{
    /**
     * @var EventDispatcher
     */
    public $eventDispatcher;

    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    public function dispatch($signal, $sender, $kwargs = [])
    {
        $this->eventDispatcher->dispatch($signal);
    }

    public function __call($name, $arguments)
    {
        call_user_func_array([$this->eventDispatcher, $name], $arguments);
    }
}
