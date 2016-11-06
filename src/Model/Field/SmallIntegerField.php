<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Doctrine\DBAL\Types\Type;

/**
 * Like an IntegerField, but only allows values under a certain (database-dependent) point.
 *
 * Values from -32768 to 32767 are safe in all databases supported by Doctrine Dbal.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class SmallIntegerField extends IntegerField
{
    /**
     * {@inheritdoc}
     */
    public function dbType($connection)
    {
        return Type::SMALLINT;
    }
}
