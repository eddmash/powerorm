<?php
/**
 * Created by Eddilbert Macharia (edd.cowan@gmail.com)<http://eddmash.com>
 * Date: 10/14/16.
 */

namespace Eddmash\PowerOrm\Model\Field\RelatedObjects;

/**
 * Class OneToOneRel
 * {@inheritdoc}
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class OneToOneRel extends ManyToOneRel
{
    public function __construct(array $kwargs = [])
    {
        parent::__construct($kwargs);
        $this->multiple = false;
    }
}
