<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Descriptors;

use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\RelatedObjectDoesNotExist;
use Eddmash\PowerOrm\Model\Manager\M2OManager;
use Eddmash\PowerOrm\Model\Manager\O2MManager;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * @inheritdoc
 *
 * Gets related data from the one side of the relationship
 *
 * user has many cars so this will query cars related to a particular user in this the
 * default attribute to be used will be ::
 *
 *  $user->car_set->all()
 *
 * Class ManyToOneDescriptor
 * @package Eddmash\PowerOrm\Model\Field\Descriptors
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class OneToManyDescriptor extends BaseDescriptor
{
    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {

        return $this->queryset($modelInstance);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        return $this->getValue($modelInstance)->set($value);
    }

    /**
     * Creates the queryset to retrieve data for the relationship that relates to this field.
     *
     * @param $modelInstance
     * @param bool $reverse
     *
     * @internal param $modelName
     *
     * @return O2MManager
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function queryset($modelInstance, $reverse = false)
    {




    }
}
