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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Model;

/**
 * Base class that all relational fields inherit from.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelatedField extends Field
{
    /**
     * Points to the model the field relates to. For example, Author in ForeignKey(['model'=>Author]).
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRelatedModel()
    {
        BaseOrm::getRegistry()->isAppReady();

        return $this->relation->toModel;
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);

        $callback = function ($kwargs) {
            /* @var $field RelatedField */
            /** @var $related Model */
            $related = $kwargs['relatedModel'];
            $field = $kwargs['fromField'];

            $field->relation->toModel = $related;
            $field->doRelatedClass($related, $kwargs['scopeModel']);
        };

        Tools::lazyRelatedOperation($callback, $this->scopeModel, $this->relation->toModel, ['fromField' => $this]);
    }

    /**
     * @param Model $relatedModel
     * @param Model $scopeModel
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function doRelatedClass($relatedModel, $scopeModel)
    {
        $this->contributeToRelatedClass($relatedModel, $scopeModel);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        if(ArrayHelper::hasKey($kwargs, 'onDelete')):
            $kwargs['onDelete'] = $this->relation->onDelete;
        endif;

        if (is_string($this->relation->toModel)):
            $kwargs['to'] = $this->relation->toModel;
        else:
            $name = $this->relation->toModel->getFullClassName();
            $kwargs['to'] = ClassHelper::getNameFromNs($name, BaseOrm::getModelsNamespace());
        endif;

        if ($this->relation->parentLink):

            $kwargs['parentLink'] = $this->relation->parentLink;
        endif;

        return $kwargs;
    }

    public function contributeToRelatedClass($relatedModel, $scopeModel)
    {

    }

}
