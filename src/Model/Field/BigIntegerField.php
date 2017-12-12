<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Db\ConnectionInterface;

/**
 * A 64 bit integer, much like an IntegerField except that it is guaranteed to fit numbers from
 * -9223372036854775808 to 9223372036854775807.
 *
 * The default form widget for this field is a TextInput.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BigIntegerField extends IntegerField
{
    /**
     * {@inheritdoc}
     */
    public function dbType(ConnectionInterface $connection)
    {
        return Type::BIGINT;
    }
}
