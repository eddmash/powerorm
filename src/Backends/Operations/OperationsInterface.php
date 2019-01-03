<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Backends\Operations;

use Eddmash\PowerOrm\Exception\NotImplemented;

interface OperationsInterface
{
    /**
     * Returns an SQL DISTINCT clause which removes duplicate rows from the
     * result set. If any fields are given, only the given fields are being
     * checked for duplicates.
     *
     * @param $distinctFields
     *
     * @return string
     *
     * @throws NotImplemented
     */
    public function distinctSql($distinctFields);
}
