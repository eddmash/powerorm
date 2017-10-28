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

use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Manager\M2MManager;
use Eddmash\PowerOrm\Model\Model;

class ManyToManyDescriptor extends BaseDescriptor
{
    /** @var RelatedField */
    protected $field;

    public function setValue(Model $modelInstance, $value)
    {
        $queryset = $this->getValue($modelInstance);
        $queryset->set($value);
    }

    public function getValue(Model $modelInstance)
    {
        return $this->queryset($modelInstance);
    }

    /**
     * {@inheritdoc}
     */
    public function queryset($modelInstance, $reverse = false)
    {
        if ($this->reverse) :
            $model = $this->field->getRelatedModel();
        else:
            $model = $this->field->scopeModel;
        endif;

        // define BaseM2MQueryset
        if (!class_exists('\Eddmash\PowerOrm\Model\Manager\BaseM2MManager', false)):
            $baseClass = $model::getManagerClass();
            $class = sprintf('namespace Eddmash\PowerOrm\Model\Manager;class BaseM2MManager extends \%s{}', $baseClass);
            eval($class);
        endif;

        $manager = M2MManager::createObject(
            [
                'model' => $model,
                'rel' => $this->field->relation,
                'instance' => $modelInstance,
                'reverse' => $this->reverse,
            ]
        );

        return $manager;
    }
}
