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

class IntegerField extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dbType($connection)
    {
        return Type::INTEGER;
    }

}
