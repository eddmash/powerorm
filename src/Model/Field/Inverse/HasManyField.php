<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Inverse;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Model;

/**
 * {@inheritdoc}
 *
 * This field specifically deals with query relations that return multiple objects.
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class HasManyField extends InverseField
{
    protected $descriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\OneToManyDescriptor';
    protected $m2mDescriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\ManyToManyDescriptor';

    public function __construct(array $kwargs)
    {
        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = ArrayHelper::getValue($kwargs, 'fromField', $this);
        if (!ArrayHelper::hasKey($kwargs, 'rel')):

            $kwargs['rel'] = $this->fromField->relation;
        endif;

        parent::__construct($kwargs);
    }

    public function getCacheName()
    {
        return $this->relation->getCacheName();
    }

    public function getForwardRelatedFilter(Model $model)
    {
        return $this->relation->fromField->getForwardRelatedFilter($model);
    }

    public function getReverseRelatedFilter(Model $model)
    {
        return $this->fromField->getForwardRelatedFilter($model);
    }

    public function getPathInfo()
    {
        $meta = $this->scopeModel->getMeta();

        return [
            [
                'fromMeta' => $meta,
                'toMeta' => $this->fromField->scopeModel->getMeta(),
                'targetFields' => [$this->fromField->scopeModel->getMeta()->primaryKey],
                'joinField' => $this->relation, //field that joins the relationship
                'm2m' => false,
                'direct' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isNull()
    {
        return $this->fromField->isNull();
    }

    /**
     * @return string
     */
    public function getDescriptor()
    {
        if ($this->fromField instanceof ManyToManyField) :
            return new $this->m2mDescriptor($this);
        endif;

        return parent::getDescriptor();
    }
}
