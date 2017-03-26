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
use Eddmash\PowerOrm\Model\Field\RelatedObjects\OneToManyRel;
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
    public function __construct(array $kwargs)
    {
        $kwargs['rel'] = OneToManyRel::createObject(
            [
                'fromField' => $this,
                'to' => ArrayHelper::getValue($kwargs, 'to'),
            ]
        );
        parent::__construct($kwargs);
        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = ArrayHelper::getValue($kwargs, 'fromField');
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(Model $modelInstance)
    {
        return $this->queryset($modelInstance, true);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(Model $modelInstance, $value)
    {
        $queryset = $this->getValue($modelInstance);
        $queryset->set($value);
    }

}
