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
    protected $descriptor = '\Eddmash\PowerOrm\Model\Field\Descriptors\ReverseManyToOneDescriptor';

    public function __construct(array $kwargs)
    {
        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = ArrayHelper::getValue($kwargs, 'fromField');
        $kwargs['rel'] = $this->fromField->relation;
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
        $meta = $this->scopeModel->meta;

        return [
            [
                'fromMeta' => $meta,
                'toMeta' => $this->fromField->scopeModel->meta,
                'targetFields' => [$this->fromField->scopeModel->meta->primaryKey],
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
}
