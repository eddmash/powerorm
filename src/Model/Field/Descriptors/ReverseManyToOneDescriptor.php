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

use Eddmash\PowerOrm\Model\Manager\M2OManager;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

class ReverseManyToOneDescriptor extends BaseDescriptor
{
    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {
        if (empty($modelInstance)):
            return $this;
        endif;

        return $this->queryset($modelInstance);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        dump('SETT');
    }

    /**
     * Creates the queryset to retrieve data for the relationship that relates to this field.
     *
     * @param      $modelInstance
     * @param bool $reverse
     *
     * @internal param $modelName
     *
     * @return Queryset
     * @author   : Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function queryset($modelInstance, $reverse = true)
    {
        if ($reverse) :
            $model = $this->field->getRelatedModel();
        else:
            $model = $this->field->scopeModel;
        endif;

        // define BaseM2MQueryset
        if (!class_exists('\Eddmash\PowerOrm\Model\Manager\BaseM2OManager', false)):
            $baseClass = $model::getManagerClass();
            $class = sprintf('namespace Eddmash\PowerOrm\Model\Manager;class BaseM2OManager extends \%s{}', $baseClass);
            eval($class);
        endif;

        $manager = M2OManager::createObject(
            [
                'model' => $model,
                'rel' => $this->field->relation,
                'instance' => $modelInstance,
                'reverse' => $reverse,
            ]
        );

        return $manager;
    }
}
