<?php

/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Results;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Query\Expression\Col;
use Eddmash\PowerOrm\Model\Query\Queryset;

class Mapper
{
    /**
     * @var Queryset
     */
    protected $queryset;

    protected $columnInfoCache = [];

    /**
     * Mapper constructor.
     */
    public function __construct(Queryset $queryset)
    {
        $this->queryset = $queryset;
    }

    /**
     * creates a mapping of model attributes with there related \Eddmash\PowerOrm\Model\Field\Field.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function populateColumnCache()
    {
        $selectColumns = $this->queryset->query->select;
        /** @var $col Col */
        foreach ($selectColumns as $selectColumn) :
            $col = $selectColumn[0];
            $this->columnInfoCache[$col->getOutputField()->getAttrName()] = $col->getOutputField();
        endforeach;
    }

    /**
     * Returns \Eddmash\PowerOrm\Model\Field\Field representing the given column.
     *
     * @param $column
     *
     * @return Field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function getField($column)
    {
        // try to populate cache if its empty.
        if (empty($this->columnInfoCache)):
            $this->populateColumnCache();
        endif;

        return ArrayHelper::getValue($this->columnInfoCache, $column, ArrayHelper::STRICT);
    }

}
