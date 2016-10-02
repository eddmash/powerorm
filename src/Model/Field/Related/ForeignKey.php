<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field\Related;

use Eddmash\PowerOrm\Model\Field\RelatedObjects\ManyToOneRel;

class ForeignKey extends RelatedField
{
    public $manyToOne = true;

    public function __construct($kwargs)
    {

        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && $kwargs['rel'] == null)):
            $kwargs['rel'] = ManyToOneRel::createObject([
                'field' => $this,
                'to' => $kwargs['to'],
                'parentLink' => $kwargs['parentLink'],
                'onDelete' => $kwargs['onDelete'],
            ]);
        endif;

        parent::__construct($kwargs);

    }
}
