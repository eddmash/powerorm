<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field\Related;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;

class RelatedField extends Field
{
    public function getRelatedModel()
    {
        BaseOrm::getRegistry()->isAppReady();

        return $this->remoteField->getRelatedModel();
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);

        $callback = function ($kwargs) {
            /* @var $field Field */
            /** @var $related Model */
            $related = $kwargs['related'];
            $field = $kwargs['field'];

            $field->remoteField->model = $related;
        };

        Tools::lazyRelatedOperation($callback, $this->scopeModel, $this->remoteField->model, ['field' => $this]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        $kwargs['onDelete'] = $this->remoteField->onDelete;

        if (is_string($this->remoteField->model)):
            $kwargs['to'] = $this->remoteField->model;
        else:
            $kwargs['to'] = $this->remoteField->model->getFullClassName();
        endif;

        if ($this->remoteField->parentLink):

            $kwargs['parentLink'] = $this->remoteField->parentLink;
        endif;

        return $kwargs;
    }

}
