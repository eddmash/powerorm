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

use DateTime;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Exception\ValidationError;

class DateField extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dbType($connection)
    {
        return Type::DATE;
    }


    public function toPhp($value)
    {
        if (is_null($value)):
            return $value;
        endif;

        $format = 'Y-m-d';
        if ($value instanceof \DateTime) :
            return $value->format($format);
        endif;

        if (is_string($value)) :
            $date = DateTime::createFromFormat('!'.$format, $value);
            if ($date):
                return $date;
            endif;

            throw new ValidationError(
                sprintf("'%s' value has an invalid date format. It must be in Y-m-d format.", $value)
            );

        endif;

        throw new ValidationError(
            sprintf("'%s' value has the correct format (Y-m-d)but it is an invalid date.", $value)
        );
    }
}
