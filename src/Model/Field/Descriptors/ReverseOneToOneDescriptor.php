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
use Eddmash\PowerOrm\Exception\ObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\RelatedObjectDoesNotExist;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Model;

class ReverseOneToOneDescriptor extends BaseDescriptor
{
    /** @var RelatedField */
    protected $field;

    public function getValue(Model $modelInstance)
    {
        try {
            // incase the value has been set
            $relObj = $modelInstance->{$this->field->getCacheName()};
        } catch (\Exception $e) {
            $relPk = $modelInstance->getPkValue();
            if (empty($relPk)):
                $relObj = null;
            else:
                $filterArgs = $this->field->getForwardRelatedFilter($modelInstance);

                try {
                    $relObj = $this->getQueryset($modelInstance)->get($filterArgs);
                    $relObj->{$this->field->getCacheName()} = $modelInstance;
                } catch (ObjectDoesNotExist $exception) {
                    $relObj = null;
                }
            endif;
            $modelInstance->{$this->field->getCacheName()} = $relObj;
        }

        if (is_null($relObj) && !$this->field->isNull()):
            throw new RelatedObjectDoesNotExist(
                sprintf(
                    '%s has no %s.',
                    $modelInstance->getMeta()->getNamespacedModelName(),
                    $this->field->relation->getAccessorName()
                )
            );
        endif;

        return $relObj;
    }

    public function setValue(Model $modelInstance, $value)
    {
        if (empty($value)):
            //Update the cached related instance (if any) & clear the cache.
            try {
                $relObj = $modelInstance->{$this->field->getCacheName()};
                unset($modelInstance->{$this->field->getCacheName()});
                unset($relObj->{$this->field->relation->getCacheName()});
            } catch (AttributeError $exception) {
            }

        else:

            $relatedPks = [];
            foreach ($this->field->relation->fromField->getForeignRelatedFields() as $foreignRelatedField) :
                $relatedPks[] = $modelInstance->{$foreignRelatedField->getAttrName()};
            endforeach;
            foreach ($this->field->relation->fromField->getLocalRelatedFields() as $idx => $localRelatedField) :
                $value->{$localRelatedField->getAttrName()} = $relatedPks[$idx];
            endforeach;

            // Set the related instance cache to avoid an SQL query
            // when accessing the attribute we just set.
            $modelInstance->{$this->field->getCacheName()} = $value;
            // Set the related instance cache to avoid an SQL query
            // when accessing the attribute we just set.
            $value->{$this->field->relation->getCacheName()} = $modelInstance;
        endif;
    }

    private function getQueryset($modelInstance)
    {
        $relModel = $this->field->relation->getFromModel();

        return $relModel::objects();
    }
}
