<?php
/**
 * Created by Eddilbert Macharia (edd.cowan@gmail.com)<http://eddmash.com>
 * Date: 10/14/16.
 */
namespace Eddmash\PowerOrm\Model\Field\RelatedObjects;

use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\ValueError;

class ManyToManyRel extends ForeignObjectRel
{
    public $multiple = true;
    public $through;
    public $through_fields;
    public $dbConstrait = true;

    public function __construct($kwargs = [])
    {

        if($this->through && !$this->dbConstrait):
            throw new ValueError("Can't supply a through model and db_constraint=False");
        endif;

        if($this->through_fields && !$this->through):
            throw new ValueError('Cannot specify through_fields without a through model');
        endif;
        parent::__construct($kwargs);
    }

    public function getRelatedField()
    {
        throw new NotImplemented();
    }
}
