<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;

class IsNull extends BaseLookup
{
    public static $lookupName = 'isnull';
    public $prepareRhs = false;

    /**
     * {@inheritdoc}
     */
    public function asSql(Connection $connection)
    {
        list($lhs_sql, $params) = $this->processLHS($connection);

        $rhs_sql = 'IS NULL';

        if (!$this->rhs):
            $rhs_sql = 'IS NOT NULL';
        endif;

        return [sprintf('%s %s', $lhs_sql, $rhs_sql), $params];
    }
}
