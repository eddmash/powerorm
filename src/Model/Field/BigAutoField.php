<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/2/18
 * Time: 6:17 PM.
 */

namespace Eddmash\PowerOrm\Model\Field;

use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Backends\ConnectionInterface;

class BigAutoField extends AutoField
{
    public function dbType(ConnectionInterface $connection)
    {
        return Type::BIGINT;
    }
}
