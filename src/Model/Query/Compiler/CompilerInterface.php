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

interface CompilerInterface
{
    public function executeSql($chunked = false);

    public function compile(SqlCompilableinterface $node);

    /**
     * Quotes columns and tables if they are not aliases.
     *
     * @return \Callable
     */
    public function quoteUnlessAliasCallback();

    /**
     * Quotes columns and tables.
     *
     * @return \Callable
     */
    public function quoteCallback();
}
