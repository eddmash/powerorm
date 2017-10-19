<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Results;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Expression\Col;

class RelatedMappers
{
    public $colsEnd;
    public $colStart;
    public $reverseCacheName;
    public $initList;
    /**
     * @var Model
     */
    private $model;
    private $connection;

    public function __construct($klassInfo, $select, $connection)
    {
        $this->model = ArrayHelper::getValue($klassInfo, 'model');
        $this->relatedPopulators = ModelMapper::getRelatedMapper($klassInfo, $select, $connection);
        $this->connection = $connection;
        $selectFields = ArrayHelper::getValue($klassInfo, 'select_fields');
        $fromParent = ArrayHelper::getValue($klassInfo, 'from_parent');

        $this->initList = [];

        if (!$fromParent):

            $this->colStart = reset($selectFields);
            $this->colsEnd = count($selectFields);

            /* @var $col Col */
            foreach (array_slice($select, $this->colStart, $this->colsEnd) as $colInfo) :
                $col = $colInfo[0];
                $this->initList[] = $col->getTargetField()->getAttrName();
            endforeach;
        else:
            // todo map from parent fields
        endif;

        /** @var $field Field */
        $field = ArrayHelper::getValue($klassInfo, 'field');

        $reverse = ArrayHelper::getValue($klassInfo, 'reverse');
        if ($reverse):
            $this->cacheName = $field->relation->getCacheName();
            $this->reverseCacheName = $field->getCacheName();
        else:
            $this->cacheName = $field->getCacheName();
            if ($field->isUnique()):
                $this->reverseCacheName = $field->relation->getCacheName();
            endif;
        endif;
    }

    public function populate($row, Model $fromObj)
    {
        $vals = array_slice($row, $this->colStart, $this->colsEnd);
        $model = $this->model;

        $relObj = $model::fromDb($this->connection, $this->initList, $vals);

        // populate its related objects if any
        if ($relObj && $this->relatedPopulators):
            foreach ($this->relatedPopulators as $relatedPopulator) :
                $relatedPopulator->populate($row, $relObj);
            endforeach;
        endif;

        // set on the original model
        $fromObj->{$this->cacheName} = $relObj;
        // the cache the original object on the related object
        if ($relObj && $this->reverseCacheName):
            $relObj->{$this->reverseCacheName} = $fromObj;
        endif;
    }

}
