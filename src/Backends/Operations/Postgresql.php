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

class Postgresql extends BaseOperations
{
    /**
     * {@inheritdoc}
     */
    public function distinctSql($distinctFields): string
    {
        if ($distinctFields) {
            return sprintf('DISTINCT ON (%s) ', implode(', ', $distinctFields));
        }
        return 'DISTINCT';
    }
}
