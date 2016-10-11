<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation;

use Doctrine\DBAL\Schema\Schema;
use Eddmash\PowerOrm\Migration\State\ProjectState;

interface OperationInterface
{
    //todo look ata djangos operation base

    /**
     * Migration use this method to contribute to the current state of the project.
     *
     * @param ProjectState $state
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function updateState($state);

    /**
     * @param Schema       $schema
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseForwards($schema, $fromState, $toState);

    /**
     * @param Schema       $schema
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseBackwards($schema, $fromState, $toState);
}
