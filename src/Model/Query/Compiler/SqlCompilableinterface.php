<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Compiler;

use Eddmash\PowerOrm\Backends\ConnectionInterface;

interface SqlCompilableinterface
{
    /**
     * if the instance passed to a compiler it can be converted into a valid Sql string.
     *
     * @param CompilerInterface   $compiler
     * @param ConnectionInterface $connection
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection);
}
