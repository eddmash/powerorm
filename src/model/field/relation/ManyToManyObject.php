<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 9/6/16
 * Time: 1:19 AM.
 */
namespace eddmash\powerorm\model\field\relation;

class ManyToManyObject extends RelationObject
{
    public $through = null;

    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->through = $opts['through'];
    }
}
