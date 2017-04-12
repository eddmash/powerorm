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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\ValidationError;
use Eddmash\PowerOrm\Model\Model;

class DateField extends Field
{
    public $autoNow;
    public $autoAddNow;

    /**
     * {@inheritdoc}
     */
    public function dbType(Connection $connection)
    {
        return Type::DATE;
    }

    /**
     * @inheritDoc
     */
    public function preSave(Model $model, $add)
    {
        if($this->autoNow || ($this->autoAddNow && $add)):
            return new DateTime('now', new \DateTimeZone(BaseOrm::getInstance()->getTimezone()));
        endif;
        return parent::preSave($model, $add);
    }


}
