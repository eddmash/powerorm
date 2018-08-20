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
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Model\Model;

class DateField extends Field
{
    /**
     * Automatically set the field to now every time the object is saved. Useful for “last-modified”
     * timestamps.
     *
     * Note that the current date is always used; it’s not just a default value that you can override.
     *
     * @var
     */
    public $autoNow;

    /**
     * Automatically set the field to now when the object is first created. Useful for creation of timestamps.
     *
     * Note that the current date is always used; it’s not just a default value that you can override.
     *
     * @var
     */
    public $autoAddNow;

    /**
     * {@inheritdoc}
     */
    public function dbType(ConnectionInterface $connection)
    {
        return Type::DATE;
    }

    /**
     * {@inheritdoc}
     */
    public function preSave(Model $model, $add)
    {
        if ($this->autoNow || ($this->autoAddNow && $add)):
            return new DateTime(
                'now',
                new \DateTimeZone(BaseOrm::getInstance()->getTimezone())
            );
        endif;

        return parent::preSave($model, $add);
    }

    /**
     * @inheritDoc
     */
    public function getConstructorArgs()
    {
        $args = parent::getConstructorArgs();

        if ($this->autoAddNow) {
            $args['autoAddNow'] = true;
        }
        if ($this->autoNow) {
            $args['autoNow'] = true;
        }

        return $args;
    }


    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\DateField::class;

        return parent::formField($kwargs);
    }

    public function valueToString(Model $model)
    {
        /*
         * @var \DateTime
         */
        $val = $this->valueFromObject($model);

        return $val->format('c');
    }
}
