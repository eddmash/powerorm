<?php
/**
 * Created by Eddilbert Macharia (edd.cowan@gmail.com)<http://eddmash.com>
 * Date: 10/14/16.
 */

namespace Eddmash\PowerOrm\Model\Field\RelatedObjects;

use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class ManyToManyRel
 * {@inheritdoc}
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class ManyToManyRel extends ForeignObjectRel
{
    public $multiple = true;

    /**
     * @var Model
     */
    public $through;

    /**
     * Only used when a custom intermediary model is specified.
     *
     * Accepts a 2-array ['field1', 'field2'], where field1 is the name of the foreign key to the model the
     * ManyToManyField is defined on.
     *
     * @var array
     */
    public $through_fields;
    public $dbConstraint = true;

    public function __construct($kwargs = [])
    {
        if ($this->through && !$this->dbConstraint):
            throw new ValueError("Can't supply a through model and db_constraint=false");
        endif;

        if ($this->through_fields && !$this->through):
            throw new ValueError('Cannot specify through_fields without a through model');
        endif;
        parent::__construct($kwargs);
    }

    public function getRelatedField()
    {
        throw new NotImplemented();
    }

}
