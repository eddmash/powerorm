<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Lookup;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\SqlCompilableinterface;

/**
 * Class Filter.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface LookupInterface extends SqlCompilableinterface
{
    public static function createObject($rhs, $lhs);

    public function processLHS(CompilerInterface $compiler, ConnectionInterface $connection);

    public function processRHS(CompilerInterface $compiler, ConnectionInterface $connection);

    public function getLookupOperation($rhs);

    public function asSql(CompilerInterface $compiler, ConnectionInterface $connection);
}
